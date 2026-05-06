<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions() as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach ($this->rolePermissions() as $roleName => $permissions) {
            Role::findOrCreate($roleName)->syncPermissions($permissions);
        }

        $demoCompany = $this->createDemoCompany();

        $this->createDemoUser('Demo Super Admin', 'super.admin@example.com', UserType::SuperAdmin, UserType::SuperAdmin->value);
        $this->createDemoUser('Demo Company Admin', 'company.admin@example.com', UserType::CompanyAdmin, UserType::CompanyAdmin->value, $demoCompany);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<int, string>
     */
    private function permissions(): array
    {
        return [
            'companies.view', 'companies.create', 'companies.update', 'companies.delete',
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.suspend', 'users.restore', 'users.invite',
            'profiles.view', 'profiles.update', 'profiles.avatar.update',
            'departments.view', 'departments.create', 'departments.update', 'departments.delete',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'tickets.view', 'tickets.view.own', 'tickets.view.department', 'tickets.view.assigned', 'tickets.create', 'tickets.reply', 'tickets.comment', 'tickets.assign', 'tickets.transfer', 'tickets.close', 'tickets.reopen', 'tickets.delete',
            'complaints.view', 'complaints.view.own', 'complaints.view.department', 'complaints.create', 'complaints.reply', 'complaints.assign', 'complaints.close', 'complaints.delete',
            'inquiries.view', 'inquiries.view.own', 'inquiries.create', 'inquiries.reply', 'inquiries.close', 'inquiries.delete',
            'settings.view', 'settings.update',
            'branding.view', 'branding.update',
            'theme.update',
            'language.update',
            'notifications.view', 'notifications.mark_read', 'notifications.delete',
            'mailbox.view', 'mailbox.read', 'mailbox.send', 'mailbox.archive', 'mailbox.delete',
            'canned_responses.view', 'canned_responses.create', 'canned_responses.update', 'canned_responses.delete',
            'knowledge_base.view', 'knowledge_base.create', 'knowledge_base.update', 'knowledge_base.delete',
            'faq.view', 'faq.create', 'faq.update', 'faq.delete',
            'custom_fields.view', 'custom_fields.create', 'custom_fields.update', 'custom_fields.delete',
            'files.view', 'files.download', 'files.delete',
            'file_policies.view', 'file_policies.update',
            'reports.view', 'reports.export',
            'activity_logs.view', 'error_logs.view',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rolePermissions(): array
    {
        $allPermissions = $this->permissions();

        return [
            UserType::SuperAdmin->value => $allPermissions,
            UserType::CompanyAdmin->value => array_values(array_diff($allPermissions, [
                'companies.delete',
                'error_logs.view',
            ])),
            UserType::DepartmentManager->value => [
                'users.view', 'profiles.view', 'profiles.update', 'departments.view',
                'tickets.view.department', 'tickets.create', 'tickets.reply', 'tickets.comment', 'tickets.assign', 'tickets.transfer', 'tickets.close', 'tickets.reopen',
                'complaints.view.department', 'complaints.reply', 'complaints.assign', 'complaints.close',
                'inquiries.view', 'inquiries.reply', 'inquiries.close',
                'notifications.view', 'notifications.mark_read',
                'mailbox.view', 'mailbox.read', 'mailbox.send', 'mailbox.archive',
                'files.view', 'files.download',
                'canned_responses.view', 'canned_responses.create', 'canned_responses.update',
                'knowledge_base.view', 'knowledge_base.create', 'knowledge_base.update',
                'faq.view', 'faq.create', 'faq.update',
                'reports.view',
            ],
            UserType::DepartmentDeputy->value => [
                'users.view', 'profiles.view', 'profiles.update', 'departments.view',
                'tickets.view.department', 'tickets.reply', 'tickets.comment', 'tickets.assign', 'tickets.close',
                'complaints.view.department', 'complaints.reply', 'complaints.assign',
                'inquiries.view', 'inquiries.reply',
                'notifications.view', 'notifications.mark_read',
                'mailbox.view', 'mailbox.read', 'mailbox.send',
                'files.view', 'files.download',
                'canned_responses.view',
                'knowledge_base.view',
                'faq.view',
                'reports.view',
            ],
            UserType::SupportAgent->value => [
                'profiles.view', 'profiles.update', 'profiles.avatar.update',
                'tickets.view.assigned', 'tickets.create', 'tickets.reply', 'tickets.comment', 'tickets.close', 'tickets.reopen',
                'complaints.view.department', 'complaints.reply',
                'inquiries.view', 'inquiries.reply',
                'notifications.view', 'notifications.mark_read',
                'mailbox.view', 'mailbox.read', 'mailbox.send',
                'files.download',
                'canned_responses.view',
                'knowledge_base.view',
                'faq.view',
            ],
            UserType::Customer->value => [
                'profiles.view', 'profiles.update', 'profiles.avatar.update',
                'tickets.view.own', 'tickets.create', 'tickets.reply',
                'complaints.view.own', 'complaints.create', 'complaints.reply',
                'inquiries.view.own', 'inquiries.create', 'inquiries.reply',
                'notifications.view', 'notifications.mark_read',
                'mailbox.view', 'mailbox.read',
                'files.download',
                'knowledge_base.view',
                'faq.view',
            ],
        ];
    }

    private function createDemoCompany(): Company
    {
        return Company::query()->firstOrCreate([
            'slug' => 'demo-company',
        ], [
            'name' => 'Demo Company',
            'email' => 'company@example.com',
            'status' => CompanyStatus::Active,
        ]);
    }

    private function createDemoUser(string $name, string $email, UserType $userType, string $role, ?Company $company = null): User
    {
        $user = User::query()->firstOrCreate([
            'email' => $email,
        ], [
            'name' => $name,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'company_id' => $company?->id,
            'user_type' => $userType,
            'status' => UserStatus::Active,
            'locale' => 'ar',
        ]);

        $user->syncRoles([$role]);

        return $user;
    }
}
