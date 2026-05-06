<?php

use App\Enums\MailboxMessageType;
use App\Enums\UserType;
use App\Events\MailboxMessageCreated;
use App\Models\Company;
use App\Models\MailboxMessage;
use App\Models\User;
use App\Repositories\Contracts\MailboxRepositoryInterface;
use App\Services\MailboxService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('mailbox service creates user specific tenant safe messages and broadcasts event', function () {
    Event::fake([MailboxMessageCreated::class]);

    $company = Company::factory()->create();
    $sender = User::factory()->create(['company_id' => $company->id]);
    $recipient = User::factory()->create(['company_id' => $company->id]);

    $message = app(MailboxService::class)->send(
        recipient: $recipient,
        subject: 'Ticket assignment',
        body: 'A ticket has been assigned to you.',
        sender: $sender,
        type: MailboxMessageType::Assignment,
        relatedType: 'ticket',
        relatedId: 123,
    );

    expect($message->recipient_id)->toBe($recipient->id)
        ->and($message->sender_id)->toBe($sender->id)
        ->and($message->company_id)->toBe($company->id)
        ->and($message->type)->toBe(MailboxMessageType::Assignment)
        ->and($message->related_type)->toBe('ticket')
        ->and($message->related_id)->toBe(123)
        ->and($message->isUnread())->toBeTrue();

    Event::assertDispatched(MailboxMessageCreated::class);
});

test('mailbox repository scopes latest unread archived and pagination to recipient', function () {
    $recipient = User::factory()->create();
    $otherRecipient = User::factory()->create();

    MailboxMessage::factory()->count(3)->create(['recipient_id' => $recipient->id, 'company_id' => $recipient->company_id]);
    MailboxMessage::factory()->read()->create(['recipient_id' => $recipient->id, 'company_id' => $recipient->company_id]);
    MailboxMessage::factory()->archived()->create(['recipient_id' => $recipient->id, 'company_id' => $recipient->company_id]);
    MailboxMessage::factory()->create(['recipient_id' => $otherRecipient->id, 'company_id' => $otherRecipient->company_id]);

    $repository = app(MailboxRepositoryInterface::class);

    expect($repository->unreadCountForRecipient($recipient))->toBe(3)
        ->and($repository->latestForRecipient($recipient, 5))->toHaveCount(4)
        ->and($repository->paginatedForRecipient($recipient)->total())->toBe(4)
        ->and($repository->paginatedForRecipient($recipient, 'unread')->total())->toBe(3)
        ->and($repository->paginatedForRecipient($recipient, 'read')->total())->toBe(1)
        ->and($repository->paginatedForRecipient($recipient, 'archived')->total())->toBe(1);
});

test('private mailbox channel authorizes the matching user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-mailbox.users.'.$user->id,
        ])
        ->assertOk();
});

test('navbar mailbox component shows unread count and can mark messages read', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create(['user_type' => UserType::Customer]);
    $user->assignRole(UserType::Customer->value);
    $message = MailboxMessage::factory()->create(['recipient_id' => $user->id, 'company_id' => $user->company_id]);

    $this->actingAs($user);

    Livewire::test('navbar.mailbox-menu')
        ->assertSet('unreadCount', 1)
        ->assertSee($message->subject)
        ->call('markAsRead', $message->id)
        ->assertSet('unreadCount', 0);

    expect($message->refresh()->read_at)->not->toBeNull();
});

test('inbox page filters marks unread and archives messages', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create(['user_type' => UserType::CompanyAdmin]);
    $user->assignRole(UserType::CompanyAdmin->value);
    $message = MailboxMessage::factory()->read()->create(['recipient_id' => $user->id, 'company_id' => $user->company_id]);
    MailboxMessage::factory()->archived()->create(['recipient_id' => $user->id, 'company_id' => $user->company_id]);

    $this->actingAs($user);

    Livewire::test('pages::mailbox.index')
        ->assertSee('Mailbox')
        ->set('status', 'read')
        ->call('markAsUnread', $message->id)
        ->assertHasNoErrors()
        ->set('status', 'unread')
        ->call('archiveMessage', $message->id)
        ->assertHasNoErrors();

    expect($message->refresh()->read_at)->toBeNull()
        ->and($message->archived_at)->not->toBeNull();
});

test('message detail page marks an unread message as read for the recipient', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create(['user_type' => UserType::Customer]);
    $user->assignRole(UserType::Customer->value);
    $message = MailboxMessage::factory()->create([
        'recipient_id' => $user->id,
        'company_id' => $user->company_id,
        'subject' => 'Admin notice',
        'body' => 'Please review this notice.',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::mailbox.show', ['message' => $message])
        ->assertSee('Admin notice')
        ->assertSee('Please review this notice.');

    expect($message->refresh()->read_at)->not->toBeNull();
});
