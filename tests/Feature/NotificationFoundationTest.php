<?php

use App\Events\SupportNotificationCreated;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\SupportNotification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Services\NotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('notification service creates user specific tenant safe notifications and broadcasts event', function () {
    Event::fake([SupportNotificationCreated::class]);

    $company = Company::factory()->create();
    $recipient = User::factory()->create(['company_id' => $company->id]);

    $notification = app(NotificationService::class)->notify(
        recipient: $recipient,
        type: 'system.alert',
        title: 'System alert',
        body: 'A new system alert is available.',
        link: '/dashboard',
        data: ['severity' => 'info'],
    );

    expect($notification->recipient_id)->toBe($recipient->id)
        ->and($notification->company_id)->toBe($company->id)
        ->and($notification->isUnread())->toBeTrue()
        ->and($notification->data)->toBe(['severity' => 'info']);

    Event::assertDispatched(SupportNotificationCreated::class);
});

test('notification repository scopes latest unread and pagination to recipient', function () {
    $recipient = User::factory()->create();
    $otherRecipient = User::factory()->create();
    SupportNotification::factory()->count(3)->create(['recipient_id' => $recipient->id, 'company_id' => $recipient->company_id]);
    SupportNotification::factory()->read()->create(['recipient_id' => $recipient->id, 'company_id' => $recipient->company_id]);
    SupportNotification::factory()->create(['recipient_id' => $otherRecipient->id, 'company_id' => $otherRecipient->company_id]);

    $repository = app(NotificationRepositoryInterface::class);

    expect($repository->unreadCountForRecipient($recipient))->toBe(3)
        ->and($repository->latestForRecipient($recipient, 5))->toHaveCount(4)
        ->and($repository->paginatedForRecipient($recipient, 'unread')->total())->toBe(3)
        ->and($repository->paginatedForRecipient($recipient, 'read')->total())->toBe(1);
});

test('private user notification channel authorizes the matching user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-users.'.$user->id,
        ])
        ->assertOk();
});

test('navbar notification component shows unread count and can mark notifications read', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create(['user_type' => UserType::Customer]);
    $user->assignRole(UserType::Customer->value);
    $notification = SupportNotification::factory()->create(['recipient_id' => $user->id, 'company_id' => $user->company_id]);

    $this->actingAs($user);

    Livewire::test('navbar.notifications-menu')
        ->assertSet('unreadCount', 1)
        ->assertSee($notification->title)
        ->call('markAsRead', $notification->id)
        ->assertSet('unreadCount', 0);

    expect($notification->refresh()->read_at)->not->toBeNull();
});

test('full notifications page filters and marks all as read', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create(['user_type' => UserType::Customer]);
    $user->assignRole(UserType::Customer->value);
    SupportNotification::factory()->count(2)->create(['recipient_id' => $user->id, 'company_id' => $user->company_id]);
    SupportNotification::factory()->read()->create(['recipient_id' => $user->id, 'company_id' => $user->company_id]);

    $this->actingAs($user);

    Livewire::test('pages::notifications.index')
        ->assertSee('Notifications')
        ->set('status', 'unread')
        ->call('markAllAsRead')
        ->assertHasNoErrors();

    expect(app(NotificationRepositoryInterface::class)->unreadCountForRecipient($user))->toBe(0);
});
