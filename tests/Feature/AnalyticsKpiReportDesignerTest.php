<?php

use App\Enums\ReportExportFormat;
use App\Enums\ScheduledReportFrequency;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeKpiTarget;
use App\Models\ReportTemplate;
use App\Models\ScheduledReport;
use App\Models\User;
use App\Services\EmployeeKpiService;
use App\Services\ScheduledReportService;
use Database\Seeders\RolesAndPermissionsSeeder;

test('manager can save employee kpi targets for an agent', function () {
    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $manager = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id]);
    $agent = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id]);

    app(EmployeeKpiService::class)->saveTarget($manager, $agent, 5, 2026, 30, 20, 4.5, 95);

    expect(EmployeeKpiTarget::query()
        ->where('company_id', $company->id)
        ->where('user_id', $agent->id)
        ->where('tickets_resolved_target', 30)
        ->exists())->toBeTrue();
});

test('scheduled reports store recipients and calculate next run', function () {
    $company = Company::factory()->create();
    $creator = User::factory()->create(['company_id' => $company->id]);

    $report = app(ScheduledReportService::class)->create(
        $creator,
        'Weekly support report',
        ScheduledReportFrequency::Weekly,
        ReportExportFormat::Excel,
        ['ops@example.com'],
    );

    expect($report)->toBeInstanceOf(ScheduledReport::class)
        ->and($report->company_id)->toBe($company->id)
        ->and($report->recipients)->toBe(['ops@example.com'])
        ->and($report->next_run_at)->not->toBeNull();
});

test('report templates export within the authenticated company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create(['name' => 'Acme Support']);
    $user = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::CompanyAdmin]);
    $user->assignRole(UserType::CompanyAdmin->value);
    $template = ReportTemplate::factory()->create([
        'company_id' => $company->id,
        'created_by_id' => $user->id,
        'format' => ReportExportFormat::Excel,
        'body' => '<table><tr><td>{{ company.name }}</td><td>{{ user.name }}</td></tr></table>',
    ]);

    $this->actingAs($user)
        ->get(route('reports.templates.export', $template))
        ->assertSuccessful()
        ->assertSee('Acme Support')
        ->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
});

test('report templates designer renders template variables literally', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::CompanyAdmin]);
    $user->assignRole(UserType::CompanyAdmin->value);

    $this->actingAs($user)
        ->get(route('reports.templates'))
        ->assertSuccessful()
        ->assertSee('{{ company.name }}')
        ->assertSee('{{ user.name }}')
        ->assertSee('{{ generated_at }}');
});
