<?php

use App\Enums\InquiryStatus;
use App\Enums\SlaAppliesTo;
use App\Enums\SlaStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\MailboxMessage;
use App\Models\SlaPolicy;
use App\Models\SupportNotification;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ErrorLogService;
use App\Services\InquiryReplyService;
use App\Services\InquiryService;
use App\Services\ReportService;
use App\Services\SlaTrackingService;
use App\Services\TicketService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

test('inquiry can be created answered assigned and converted to ticket', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $manager = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::DepartmentManager]);
    $agent = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::SupportAgent]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $manager->assignRole(UserType::DepartmentManager->value);
    $agent->assignRole(UserType::SupportAgent->value);
    $customer->assignRole(UserType::Customer->value);

    $inquiry = app(InquiryService::class)->createInquiry($customer, [
        'department_id' => $department->id,
        'subject' => 'How does onboarding work?',
        'body' => 'I need more information about onboarding.',
    ]);

    app(InquiryService::class)->assignInquiry($inquiry, $manager, $agent);
    app(InquiryReplyService::class)->addReply($inquiry->refresh(), $agent, 'Here is the answer.');
    $ticket = app(InquiryService::class)->convertToTicket($inquiry->refresh(), $manager);

    expect($inquiry->refresh()->status)->toBe(InquiryStatus::ConvertedToTicket)
        ->and($ticket)->toBeInstanceOf(Ticket::class)
        ->and(MailboxMessage::query()->where('recipient_id', $agent->id)->where('type', 'assignment')->exists())->toBeTrue()
        ->and(SupportNotification::query()->where('recipient_id', $customer->id)->where('type', 'inquiry.answered')->exists())->toBeTrue();
});

test('sla records attach to new tickets and breach checker escalates overdue records', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $admin = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::CompanyAdmin]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $admin->assignRole(UserType::CompanyAdmin->value);
    $customer->assignRole(UserType::Customer->value);
    SlaPolicy::factory()->create([
        'company_id' => $company->id,
        'applies_to' => SlaAppliesTo::Tickets,
        'first_response_minutes' => 1,
        'resolution_minutes' => 1,
    ]);

    $ticket = app(TicketService::class)->createTicket($customer, [
        'department_id' => $department->id,
        'title' => 'Slow response',
        'description' => 'Please help quickly.',
    ]);

    $record = $ticket->slaRecord()->first();
    $record->forceFill(['first_response_due_at' => now()->subMinute(), 'resolution_due_at' => now()->subMinute()])->save();

    $count = app(SlaTrackingService::class)->checkBreaches();

    expect($count)->toBe(1)
        ->and($record->refresh()->status)->toBe(SlaStatus::Breached)
        ->and(MailboxMessage::query()->where('recipient_id', $admin->id)->where('type', 'escalation')->exists())->toBeTrue();
});

test('activity error logs and reports pages are accessible to authorized users', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::CompanyAdmin]);
    $superAdmin = User::factory()->create(['user_type' => UserType::SuperAdmin]);
    $admin->assignRole(UserType::CompanyAdmin->value);
    $superAdmin->assignRole(UserType::SuperAdmin->value);

    activity()->causedBy($admin)->event('test.event')->withProperties(['company_id' => $company->id])->log('Test event');
    app(ErrorLogService::class)->record(new RuntimeException('Diagnostic failure'));

    $this->actingAs($admin);
    Livewire::test('pages::activity-logs.index')->assertSee('Test event');
    Livewire::test('pages::reports.index')->assertSee('Total Tickets');

    $this->actingAs($superAdmin);
    Livewire::test('pages::error-logs.index')->assertSee('Diagnostic failure');
    expect(app(ReportService::class)->dashboard($admin))->toHaveKey('total_tickets');
});
