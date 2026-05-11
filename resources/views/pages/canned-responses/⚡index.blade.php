<?php

use App\Models\CannedResponse;
use App\Services\CannedResponseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Canned responses')] class extends Component
{
 use AuthorizesRequests;

 public string $title = '';
 public string $body = '';
 public string $category = '';

 public function mount(): void { $this->authorize('viewAny', CannedResponse::class); }

 public function create(CannedResponseService $responses): void
 {
  $this->authorize('create', CannedResponse::class);
  $validated = $this->validate(['title' => ['required', 'max:255'], 'body' => ['required'], 'category' => ['nullable', 'max:255']]);
  $responses->create(Auth::user(), $validated + ['department_id' => Auth::user()->department_id, 'is_active' => true]);
  $this->reset(['title', 'body', 'category']);
 }

 public function with(): array
 {
  return ['responses' => CannedResponse::query()->where('company_id', Auth::user()->company_id)->latest()->get()];
 }
}; ?>

<div class="flex flex-col gap-6">
 <div><flux:heading size="xl">{{ __('Canned responses') }}</flux:heading><flux:text>{{ __('Reusable reply templates for support teams.') }}</flux:text></div>
 @can('create', \App\Models\CannedResponse::class)
  <form wire:submit="create" class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
   <flux:input wire:model="title":label="__('Title')"/>
   <flux:input wire:model="category":label="__('Category')"/>
   <flux:textarea wire:model="body":label="__('Body')"rows="4"/>
   <flux:button type="submit" variant="primary" class="justify-self-end">{{ __('Save') }}</flux:button>
  </form>
 @endcan
 <div class="grid gap-3 md:grid-cols-2">
  @foreach ($responses as $response)
   <div wire:key="canned-response-{{ $response->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading size="sm">{{ $response->title }}</flux:heading>
    <flux:text class="line-clamp-3">{{ $response->body }}</flux:text>
   </div>
  @endforeach
 </div>
</div>
