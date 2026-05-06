<?php

use App\Enums\TicketStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\MailboxMessage;
use App\Models\SupportNotification;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\TicketService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

test('customer can create a tenant scoped ticket with readable ticket number and notification side effect', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $customer->assignRole(UserType::Customer->value);

    $ticket = app(TicketService::class)->createTicket($customer, [
        'department_id' => $department->id,
        'title' => 'Cannot access the portal',
        'description' => 'The portal returns an error after login.',
    ]);

    expect($ticket->company_id)->toBe($company->id)
        ->and($ticket->department_id)->toBe($department->id)
        ->and($ticket->customer_id)->toBe($customer->id)
        ->and($ticket->ticket_number)->toStartWith('TCK-')
        ->and($ticket->status)->toBe(TicketStatus::New);

    $this->assertDatabaseHas('support_notifications', [
        'recipient_id' => $customer->id,
        'type' => 'ticket.created',
    ]);
});

test('ticket assignment records history and sends mailbox message to assigned agent', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $manager = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::DepartmentManager]);
    $agent = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::SupportAgent]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $manager->assignRole(UserType::DepartmentManager->value);
    $agent->assignRole(UserType::SupportAgent->value);
    $customer->assignRole(UserType::Customer->value);

    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create();

    app(TicketService::class)->assignTicket($ticket, $manager, $agent, 'Take ownership');

    expect($ticket->refresh()->assigned_to_id)->toBe($agent->id)
        ->and($ticket->assignments)->toHaveCount(1)
        ->and(MailboxMessage::query()->where('recipient_id', $agent->id)->where('type', 'assignment')->exists())->toBeTrue();
});

test('ticket repository scopes list results by user visibility', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $customer->assignRole(UserType::Customer->value);
    $ownTicket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create(['title' => 'Visible ticket']);
    Ticket::factory()->create(['company_id' => $otherCompany->id]);

    $tickets = app(TicketRepositoryInterface::class)->paginatedForUser($customer);

    expect($tickets->total())->toBe(1)
        ->and($tickets->first()->id)->toBe($ownTicket->id);
});

test('ticket list and create pages render for authorized customer', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    TicketPriority::factory()->create(['company_id' => $company->id]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $customer->assignRole(UserType::Customer->value);
    Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create(['title' => 'List page ticket']);

    $this->actingAs($customer);

    Livewire::test('pages::tickets.index')
        ->assertSee('List page ticket')
        ->set('search', 'List page')
        ->assertHasNoErrors();

    Livewire::test('pages::tickets.create')
        ->set('departmentId', (string) $department->id)
        ->set('title', 'New customer ticket')
        ->set('description', 'This issue needs help from support.')
        ->call('create')
        ->assertRedirect();

    expect(Ticket::query()->where('title', 'New customer ticket')->exists())->toBeTrue();
});
