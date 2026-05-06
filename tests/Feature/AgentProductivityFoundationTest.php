<?php

use App\Enums\TicketPresenceAction;
use App\Enums\TicketStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CustomerSatisfactionSurvey;
use App\Models\Department;
use App\Models\SupportNotification;
use App\Models\Ticket;
use App\Models\TicketMention;
use App\Models\User;
use App\Services\TicketCommentService;
use App\Services\TicketPresenceService;
use App\Services\TicketService;
use App\Services\TicketTimeTrackingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Date;

test('internal comment mentions create tenant scoped notifications', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $commenter = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::SupportAgent]);
    $mentioned = User::factory()->create(['name' => 'Ahmed Hassan', 'email' => 'ahmed@example.com', 'company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::SupportAgent]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create(['assigned_to_id' => $commenter->id]);

    app(TicketCommentService::class)->addComment($ticket, $commenter, 'Please review this @ahmed before we reply.');

    expect(TicketMention::query()->where('mentioned_user_id', $mentioned->id)->where('company_id', $company->id)->exists())->toBeTrue()
        ->and(SupportNotification::query()->where('recipient_id', $mentioned->id)->where('type', 'ticket.mention')->exists())->toBeTrue();
});

test('ticket timer records actual working seconds for an agent', function () {
    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $agent = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::SupportAgent]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create(['assigned_to_id' => $agent->id]);

    Date::setTestNow('2026-05-06 10:00:00');
    app(TicketTimeTrackingService::class)->start($ticket, $agent);

    Date::setTestNow('2026-05-06 10:05:00');
    $entry = app(TicketTimeTrackingService::class)->stop($ticket, $agent);

    expect($entry?->duration_seconds)->toBe(300)
        ->and(app(TicketTimeTrackingService::class)->secondsForTicket($ticket, $agent))->toBe(300);

    Date::setTestNow();
});

test('presence service exposes active colleagues on the same ticket only', function () {
    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $agent = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id]);
    $colleague = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id]);
    $customer = User::factory()->create(['company_id' => $company->id]);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create();

    app(TicketPresenceService::class)->touch($ticket, $agent, TicketPresenceAction::Viewing);
    app(TicketPresenceService::class)->touch($ticket, $colleague, TicketPresenceAction::Replying);

    $activeColleagues = app(TicketPresenceService::class)->activeColleagues($ticket, $agent);

    expect($activeColleagues)->toHaveCount(1)
        ->and($activeColleagues->first()->user_id)->toBe($colleague->id)
        ->and($activeColleagues->first()->action)->toBe(TicketPresenceAction::Replying);
});

test('closing a ticket creates a customer satisfaction survey shell', function () {
    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $agent = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id]);
    $customer = User::factory()->create(['company_id' => $company->id]);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create([
        'assigned_to_id' => $agent->id,
        'status' => TicketStatus::Open,
    ]);

    app(TicketService::class)->changeStatus($ticket, $agent, TicketStatus::Closed, 'Completed');

    expect(CustomerSatisfactionSurvey::query()->where('ticket_id', $ticket->id)->exists())->toBeTrue();
});
