<?php

use App\Models\Company;
use App\Services\KnowledgeBaseService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('FAQ')] class extends Component
{
 public string $companyId = '';

 public function mount(): void
 {
  abort_unless(Auth::user()->can('faq.view'), 403);
  $this->companyId = (string) (Auth::user()->company_id ?? Company::query()->orderBy('name')->value('id') ?? '');
 }

 public function with(KnowledgeBaseService $knowledge): array
 {
  $company = Auth::user()->company_id !== null
   ? Auth::user()->company
   : Company::query()->find($this->companyId);

  return [
   'faqs' => $knowledge->faqs($company),
   'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
  ];
 }
}; ?>

<div class="flex flex-col gap-6">
 <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
  <div>
   <flux:heading size="xl">{{ __('FAQ') }}</flux:heading>
   <flux:text>{{ __('Frequently asked questions.') }}</flux:text>
  </div>
  @if (auth()->user()->company_id === null)
   <flux:select wire:model.live="companyId":label="__('Company')" class="sm:w-72">
    @foreach ($companies as $company)
     <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
    @endforeach
   </flux:select>
  @endif
 </div>
 <div class="grid gap-3">
  @forelse ($faqs as $faq)
   <div wire:key="faq-{{ $faq->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex items-start justify-between gap-3">
     <flux:heading size="sm">{{ $faq->question }}</flux:heading>
     @if (auth()->user()->company_id === null)
      <flux:badge size="sm">{{ $faq->company?->name }}</flux:badge>
     @endif
    </div>
    <flux:text class="mt-2">{{ $faq->answer }}</flux:text>
   </div>
  @empty
   <div class="rounded-lg border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
    <flux:heading size="md">{{ __('No FAQ entries yet.') }}</flux:heading>
   </div>
  @endforelse
 </div>
</div>
