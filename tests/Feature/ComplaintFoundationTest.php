<?php

use App\Enums\ComplaintSeverity;
use App\Enums\ComplaintStatus;
use App\Enums\ReplyVisibility;
use App\Enums\UserType;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\Department;
use App\Models\MailboxMessage;
use App\Models\SupportNotification;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ComplaintReplyService;
use App\Services\ComplaintService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('customer can create complaint with related ticket and notifies relevant staff', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $admin = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::CompanyAdmin]);
    $manager = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::DepartmentManager]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $admin->assignRole(UserType::CompanyAdmin->value);
    $manager->assignRole(UserType::DepartmentManager->value);
    $customer->assignRole(UserType::Customer->value);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create();

    $complaint = app(ComplaintService::class)->createComplaint($customer, [
        'department_id' => $department->id,
        'related_ticket_id' => $ticket->id,
        'severity' => ComplaintSeverity::High,
        'title' => 'Support response concern',
        'description' => 'The ticket handling needs a formal review.',
    ]);

    expect($complaint->company_id)->toBe($company->id)
        ->and($complaint->complaint_number)->toStartWith('CMP-')
        ->and($complaint->status)->toBe(ComplaintStatus::New)
        ->and($complaint->related_ticket_id)->toBe($ticket->id)
        ->and($complaint->statusHistories()->count())->toBe(1)
        ->and(SupportNotification::query()->where('recipient_id', $admin->id)->where('type', 'complaint.created')->exists())->toBeTrue()
        ->and(SupportNotification::query()->where('recipient_id', $manager->id)->where('type', 'complaint.created')->exists())->toBeTrue();
});

test('complaint status changes notify customer and escalation sends mailbox message', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $admin = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::CompanyAdmin]);
    $manager = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::DepartmentManager]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $admin->assignRole(UserType::CompanyAdmin->value);
    $manager->assignRole(UserType::DepartmentManager->value);
    $complaint = Complaint::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create();

    app(ComplaintService::class)->changeStatus($complaint, $manager, ComplaintStatus::UnderReview, 'Initial review started');
    app(ComplaintService::class)->escalateComplaint($complaint->refresh(), $manager, 'Sensitive complaint');

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Escalated)
        ->and(SupportNotification::query()->where('recipient_id', $customer->id)->where('type', 'complaint.status_changed')->exists())->toBeTrue()
        ->and(MailboxMessage::query()->where('recipient_id', $admin->id)->where('type', 'escalation')->exists())->toBeTrue();
});

test('complaint replies support internal visibility and attachments', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $agent = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::SupportAgent]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $agent->assignRole(UserType::SupportAgent->value);
    $customer->assignRole(UserType::Customer->value);
    $complaint = Complaint::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create(['assigned_to_id' => $agent->id]);

    $reply = app(ComplaintReplyService::class)->addReply($complaint, $agent, 'Internal handling note', ReplyVisibility::Internal, [
        UploadedFile::fake()->create('evidence.txt', 2, 'text/plain'),
    ]);
    $attachment = $reply->attachments->first();

    expect($reply->visibility)->toBe(ReplyVisibility::Internal)
        ->and($attachment)->toBeInstanceOf(Attachment::class)
        ->and($agent->can('view', $attachment))->toBeTrue()
        ->and($customer->can('view', $attachment))->toBeFalse();
});

test('complaints pages list create and show for authorized customer', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $customer->assignRole(UserType::Customer->value);
    $complaint = Complaint::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create(['title' => 'Visible complaint']);

    $this->actingAs($customer);

    Livewire::test('pages::complaints.index')
        ->assertSee('Visible complaint')
        ->set('search', 'Visible')
        ->assertHasNoErrors();

    Livewire::test('pages::complaints.create')
        ->set('departmentId', (string) $department->id)
        ->set('severity', ComplaintSeverity::Medium->value)
        ->set('title', 'New complaint')
        ->set('description', 'This complaint should be reviewed carefully.')
        ->call('create')
        ->assertRedirect();

    Livewire::test('pages::complaints.show', ['complaint' => $complaint])
        ->assertSee('Visible complaint')
        ->set('replyBody', 'Customer follow-up')
        ->call('addReply')
        ->assertHasNoErrors();

    expect(ComplaintReply::query()->where('body', 'Customer follow-up')->exists())->toBeTrue();
});
