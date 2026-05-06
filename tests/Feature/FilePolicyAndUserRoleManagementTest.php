<?php

use App\Enums\AttachmentVisibility;
use App\Enums\UserType;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Department;
use App\Models\FileUploadPolicy;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\FileUploadPolicyService;
use App\Services\RoleManagementService;
use App\Services\UserInvitationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('company upload policy is enforced before attachments are stored', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $admin = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::CompanyAdmin]);
    $admin->assignRole(UserType::CompanyAdmin->value);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $admin)->create();

    $policy = app(FileUploadPolicyService::class)->policyFor($company);
    app(FileUploadPolicyService::class)->update($policy, [
        'allowed_mime_types' => ['application/pdf'],
        'max_file_size_kb' => 10,
        'max_files_per_request' => 1,
        'allow_public_attachments' => true,
        'allow_internal_attachments' => true,
    ]);

    app(AttachmentService::class)->storeFor(
        $ticket,
        $admin,
        UploadedFile::fake()->create('guide.pdf', 1, 'application/pdf'),
    );

    expect(Attachment::query()->count())->toBe(1);

    app(AttachmentService::class)->storeFor(
        $ticket,
        $admin,
        UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
    );
})->throws(ValidationException::class);

test('file manager and downloads respect internal attachment authorization', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $customer->assignRole(UserType::Customer->value);
    $admin = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::CompanyAdmin]);
    $admin->assignRole(UserType::CompanyAdmin->value);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create();
    $comment = TicketComment::factory()->create(['company_id' => $company->id, 'ticket_id' => $ticket->id, 'user_id' => $admin->id]);
    $path = 'attachments/'.$company->id.'/secret.txt';
    Storage::disk('local')->put($path, 'classified');
    $attachment = Attachment::factory()->internal()->create([
        'company_id' => $company->id,
        'uploaded_by_id' => $admin->id,
        'attachable_type' => TicketComment::class,
        'attachable_id' => $comment->id,
        'original_name' => 'secret.txt',
        'path' => $path,
        'disk' => 'local',
    ]);

    $this->actingAs($customer)->get(route('attachments.download', $attachment))->assertForbidden();
    $this->actingAs($admin)->get(route('attachments.download', $attachment))->assertSuccessful();

    Livewire::actingAs($admin)
        ->test('pages::file-manager.index')
        ->assertSee('secret.txt');
});

test('admins can invite users and manage roles without exposing super admin to company admins', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $admin = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::CompanyAdmin]);
    $admin->assignRole(UserType::CompanyAdmin->value);

    $invitation = app(UserInvitationService::class)->invite($admin, [
        'name' => 'Support Agent',
        'email' => 'agent@example.com',
        'user_type' => UserType::SupportAgent,
        'role_name' => UserType::SupportAgent->value,
        'department_id' => $department->id,
    ]);

    $agent = User::where('email', 'agent@example.com')->firstOrFail();

    expect($invitation->token)->toHaveLength(64)
        ->and($agent->hasRole(UserType::SupportAgent->value))->toBeTrue()
        ->and($admin->can('update', Role::findByName(UserType::SuperAdmin->value)))->toBeFalse();

    $role = app(RoleManagementService::class)->create($admin, 'Escalation Lead', ['tickets.view.department']);

    expect($role->name)->toStartWith('company_'.$company->id)
        ->and($role->hasPermissionTo('tickets.view.department'))->toBeTrue();
});
