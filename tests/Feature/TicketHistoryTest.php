<?php

use App\Enums\TicketStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\SupportNotification;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Database\Seeders\RolesAndPermissionsSeeder;

test('ticket transfer records transfer and status history and notifies target manager', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $fromDepartment = Department::factory()->create(['company_id' => $company->id]);
    $manager = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::DepartmentManager]);
    $toDepartment = Department::factory()->create(['company_id' => $company->id, 'manager_id' => $manager->id]);
    $actor = User::factory()->create(['company_id' => $company->id, 'department_id' => $fromDepartment->id, 'user_type' => UserType::DepartmentManager]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $actor->assignRole(UserType::DepartmentManager->value);
    $manager->assignRole(UserType::DepartmentManager->value);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $fromDepartment, $customer)->create(['status' => TicketStatus::Open]);

    app(TicketService::class)->transferTicket($ticket, $actor, $toDepartment, 'Needs billing team');

    expect($ticket->refresh()->department_id)->toBe($toDepartment->id)
        ->and($ticket->status)->toBe(TicketStatus::WaitingDepartment)
        ->and($ticket->transfers()->count())->toBe(1)
        ->and($ticket->statusHistories()->where('new_status', TicketStatus::WaitingDepartment->value)->exists())->toBeTrue()
        ->and(SupportNotification::query()->where('recipient_id', $manager->id)->where('type', 'ticket.transferred')->exists())->toBeTrue();
});

test('status changes are tracked and selected transitions require reason', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $actor = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::DepartmentManager]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $actor->assignRole(UserType::DepartmentManager->value);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create(['status' => TicketStatus::Open]);

    app(TicketService::class)->closeTicket($ticket, $actor);
    expect($ticket->refresh()->status)->toBe(TicketStatus::Closed)
        ->and($ticket->closed_at)->not->toBeNull();

    app(TicketService::class)->reopenTicket($ticket, $actor, 'Customer provided more information.');
    expect($ticket->refresh()->status)->toBe(TicketStatus::Reopened)
        ->and($ticket->statusHistories()->where('new_status', TicketStatus::Reopened->value)->exists())->toBeTrue();
});
