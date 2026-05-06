<?php

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Schema;

test('customer portal renders only the authenticated customers own data', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $customer->assignRole(UserType::Customer->value);
    $otherCustomer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $ownTicket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create(['title' => 'Visible portal ticket']);
    Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $otherCustomer)->create(['title' => 'Hidden portal ticket']);
    KnowledgeBaseArticle::factory()->create([
        'company_id' => $company->id,
        'author_id' => $customer->id,
        'title' => 'Password help',
        'slug' => 'password-help',
        'content' => 'Use the password reset page.',
        'status' => ArticleStatus::Published,
        'visibility' => ArticleVisibility::Public,
        'published_at' => now(),
    ]);

    $this->actingAs($customer)
        ->get(route('portal.dashboard'))
        ->assertSuccessful()
        ->assertSee('Visible portal ticket')
        ->assertSee('Password help')
        ->assertDontSee('Hidden portal ticket');

    $this->actingAs($customer)
        ->get(route('portal.tickets.show', $ownTicket))
        ->assertSuccessful();
});

test('security and performance indexes are present for critical query paths', function () {
    expect(Schema::hasTable('file_upload_policies'))->toBeTrue()
        ->and(Schema::hasTable('user_invitations'))->toBeTrue();

    $indexes = collect(Schema::getIndexes('tickets'))->pluck('name');

    expect($indexes)->toContain('tickets_company_status_created_idx')
        ->and($indexes)->toContain('tickets_customer_created_idx');
});
