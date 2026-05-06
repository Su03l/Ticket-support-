<?php

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Services\CompanyService;

test('company plan subscription and user relationships are available', function () {
    $plan = Plan::factory()->create();
    $company = Company::factory()->for($plan)->create();
    $subscription = Subscription::factory()
        ->for($company)
        ->for($plan)
        ->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    expect($company->plan->is($plan))->toBeTrue()
        ->and($company->subscriptions)->toHaveCount(1)
        ->and($company->subscriptions->first()->is($subscription))->toBeTrue()
        ->and($company->users->first()->is($user))->toBeTrue()
        ->and($plan->companies->first()->is($company))->toBeTrue()
        ->and($plan->subscriptions->first()->is($subscription))->toBeTrue()
        ->and($subscription->company->is($company))->toBeTrue()
        ->and($subscription->plan->is($plan))->toBeTrue()
        ->and($user->company->is($company))->toBeTrue();
});

test('company service creates companies with unique slugs and status changes', function () {
    $service = app(CompanyService::class);

    $firstCompany = $service->createCompany(['name' => 'Acme Support']);
    $secondCompany = $service->createCompany(['name' => 'Acme Support']);

    expect($firstCompany->slug)->toBe('acme-support')
        ->and($secondCompany->slug)->toBe('acme-support-2')
        ->and($firstCompany->status)->toBe(CompanyStatus::Active);

    $suspendedCompany = $service->suspend($firstCompany);

    expect($suspendedCompany->status)->toBe(CompanyStatus::Suspended)
        ->and($suspendedCompany->suspended_at)->not->toBeNull();

    $activeCompany = $service->activate($suspendedCompany);

    expect($activeCompany->status)->toBe(CompanyStatus::Active)
        ->and($activeCompany->suspended_at)->toBeNull();
});

test('repository contracts resolve from the container', function () {
    expect(app(CompanyRepositoryInterface::class))->toBeInstanceOf(CompanyRepositoryInterface::class)
        ->and(app(PlanRepositoryInterface::class))->toBeInstanceOf(PlanRepositoryInterface::class)
        ->and(app(SubscriptionRepositoryInterface::class))->toBeInstanceOf(SubscriptionRepositoryInterface::class);
});

test('subscription repository returns active company subscription', function () {
    $company = Company::factory()->create();
    $inactiveSubscription = Subscription::factory()
        ->for($company)
        ->create(['status' => SubscriptionStatus::Cancelled]);
    $activeSubscription = Subscription::factory()
        ->for($company)
        ->create(['status' => SubscriptionStatus::Active]);

    $repository = app(SubscriptionRepositoryInterface::class);

    expect($repository->activeForCompany($company)->is($activeSubscription))->toBeTrue()
        ->and($repository->forCompany($company)->contains($inactiveSubscription))->toBeTrue();
});
