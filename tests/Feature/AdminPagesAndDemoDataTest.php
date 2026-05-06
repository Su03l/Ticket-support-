<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Faq;
use App\Models\ReportTemplate;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

test('demo data seeder fills operational pages with realistic records', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(DemoDataSeeder::class);

    expect(Company::query()->where('slug', 'riyadh-operations')->exists())->toBeTrue()
        ->and(Department::query()->count())->toBeGreaterThan(0)
        ->and(Ticket::query()->count())->toBeGreaterThan(0)
        ->and(Faq::query()->count())->toBeGreaterThan(0)
        ->and(ReportTemplate::query()->count())->toBeGreaterThan(0);
});

test('super admin can open companies departments faq and settings file policy pages', function (string $routeName) {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(DemoDataSeeder::class);

    $superAdmin = User::query()->where('email', 'super.admin@example.com')->firstOrFail();

    $this->actingAs($superAdmin)
        ->get(route($routeName))
        ->assertSuccessful();
})->with([
    'companies.index',
    'departments.index',
    'faqs.index',
    'file-policies.edit',
    'working-hours.edit',
]);

test('company admin sees dashboard content instead of skeleton placeholders', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(DemoDataSeeder::class);

    $admin = User::query()->where('email', 'maha.admin@example.com')->firstOrFail();
    $admin->assignRole(UserType::CompanyAdmin->value);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('TCK-202605-1001')
        ->assertDontSee('placeholder-pattern');
});

test('custom 404 page includes status number and navigation actions', function () {
    $this->get('/missing-demo-page')
        ->assertNotFound()
        ->assertSee('404')
        ->assertSee('Home');
});
