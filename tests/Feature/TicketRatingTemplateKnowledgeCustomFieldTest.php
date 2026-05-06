<?php

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Enums\CustomFieldAppliesTo;
use App\Enums\CustomFieldType;
use App\Enums\TicketStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CannedResponse;
use App\Models\CustomFieldValue;
use App\Models\Faq;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CannedResponseService;
use App\Services\CustomFieldService;
use App\Services\KnowledgeBaseService;
use App\Services\ReportService;
use App\Services\TicketRatingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

test('customer can rate own closed ticket once and rating contributes to reports', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $customer->assignRole(UserType::Customer->value);
    $ticket = Ticket::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'status' => TicketStatus::Closed]);

    $rating = app(TicketRatingService::class)->rate($ticket, $customer, 5, 'Excellent help.');

    expect($rating->rating)->toBe(5)
        ->and(app(ReportService::class)->dashboard($customer)['average_ticket_rating'])->toBe(5.0);
});

test('canned responses are scoped and can be listed for support users', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $agent = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::SupportAgent]);
    $agent->assignRole(UserType::SupportAgent->value);

    app(CannedResponseService::class)->create($agent, [
        'title' => 'Greeting',
        'body' => 'Thanks for contacting support.',
        'category' => 'General',
        'department_id' => null,
        'is_active' => true,
    ]);

    $this->actingAs($agent);

    Livewire::test('pages::canned-responses.index')->assertSee('Greeting');
    expect(app(CannedResponseService::class)->activeForUser($agent))->toHaveCount(1);
});

test('knowledge base articles faqs and custom fields can be managed as company data', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::CompanyAdmin]);
    $admin->assignRole(UserType::CompanyAdmin->value);

    $article = KnowledgeBaseArticle::query()->create([
        'company_id' => $company->id,
        'author_id' => $admin->id,
        'title' => 'Reset password',
        'slug' => 'reset-password',
        'content' => 'Use the reset password page.',
        'visibility' => ArticleVisibility::Public,
        'status' => ArticleStatus::Published,
        'published_at' => now(),
    ]);
    Faq::query()->create(['company_id' => $company->id, 'question' => 'How?', 'answer' => 'Carefully.', 'is_active' => true]);
    $field = app(CustomFieldService::class)->create($admin, [
        'label' => 'Asset tag',
        'applies_to' => CustomFieldAppliesTo::Ticket,
        'type' => CustomFieldType::Text,
        'validation_rules' => ['string', 'unsafe_rule'],
    ]);
    $ticket = Ticket::factory()->create(['company_id' => $company->id, 'customer_id' => $admin->id]);
    app(CustomFieldService::class)->saveValues($ticket, [$field->id => 'LAP-123']);

    $this->actingAs($admin);

    Livewire::test('pages::knowledge-base.index')->assertSee('Reset password');
    Livewire::test('pages::knowledge-base.show', ['article' => $article])->assertSee('Use the reset password page.');
    Livewire::test('pages::faqs.index')->assertSee('How?');
    Livewire::test('pages::custom-fields.index')->assertSee('Asset tag');

    expect(CustomFieldValue::query()->where('fieldable_id', $ticket->id)->exists())->toBeTrue()
        ->and($field->validation_rules)->toBe(['string']);
});
