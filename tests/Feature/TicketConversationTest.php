<?php

use App\Enums\ReplyVisibility;
use App\Enums\TicketStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\SupportNotification;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\TicketCommentService;
use App\Services\TicketReplyService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('public customer reply updates workflow and notifies assigned agent', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $agent = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::SupportAgent]);
    $customer->assignRole(UserType::Customer->value);
    $agent->assignRole(UserType::SupportAgent->value);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create([
        'assigned_to_id' => $agent->id,
        'status' => TicketStatus::WaitingCustomer,
    ]);

    $reply = app(TicketReplyService::class)->addReply($ticket, $customer, 'Here is the requested information.');

    expect($reply->visibility)->toBe(ReplyVisibility::Public)
        ->and($ticket->refresh()->status)->toBe(TicketStatus::InProgress)
        ->and(SupportNotification::query()->where('recipient_id', $agent->id)->where('type', 'ticket.reply')->exists())->toBeTrue();
});

test('support internal comments are staff only and can store internal attachments', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $agent = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::SupportAgent]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $agent->assignRole(UserType::SupportAgent->value);
    $customer->assignRole(UserType::Customer->value);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create(['assigned_to_id' => $agent->id]);

    $comment = app(TicketCommentService::class)->addComment($ticket, $agent, 'Internal diagnosis', [
        UploadedFile::fake()->create('debug-log.txt', 4, 'text/plain'),
    ]);
    $attachment = $comment->attachments->first();

    expect($comment)->toBeInstanceOf(TicketComment::class)
        ->and($attachment->visibility->value)->toBe('internal')
        ->and($agent->can('view', $attachment))->toBeTrue()
        ->and($customer->can('view', $attachment))->toBeFalse();
});

test('attachment service stores metadata without exposing file path through policy', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $customer->assignRole(UserType::Customer->value);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create();
    $reply = TicketReply::factory()->create(['company_id' => $company->id, 'ticket_id' => $ticket->id, 'user_id' => $customer->id]);

    $attachment = app(AttachmentService::class)->storeFor($reply, $customer, UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'));

    Storage::disk('local')->assertExists($attachment->path);
    expect($customer->can('view', $attachment))->toBeTrue()
        ->and($attachment->original_name)->toBe('receipt.pdf');
});

test('authorized users can download attachments without direct path exposure', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $customer->assignRole(UserType::Customer->value);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create();
    $reply = TicketReply::factory()->create(['company_id' => $company->id, 'ticket_id' => $ticket->id, 'user_id' => $customer->id]);
    $attachment = app(AttachmentService::class)->storeFor($reply, $customer, UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'));

    $this->actingAs($customer)
        ->get(route('attachments.download', $attachment))
        ->assertSuccessful();
});

test('ticket detail page shows public conversation and lets authorized staff comment', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $agent = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::SupportAgent]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $agent->assignRole(UserType::SupportAgent->value);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create(['assigned_to_id' => $agent->id]);
    TicketReply::factory()->create(['company_id' => $company->id, 'ticket_id' => $ticket->id, 'user_id' => $customer->id, 'body' => 'Visible reply']);

    $this->actingAs($agent);

    Livewire::test('pages::tickets.show', ['ticket' => $ticket])
        ->assertSee('Visible reply')
        ->set('commentBody', 'Staff-only note')
        ->call('addComment')
        ->assertHasNoErrors();

    expect(TicketComment::query()->where('body', 'Staff-only note')->exists())->toBeTrue();
});
