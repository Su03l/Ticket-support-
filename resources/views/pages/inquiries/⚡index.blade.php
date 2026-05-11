<?php

use App\Enums\InquiryStatus;
use App\Models\Department;
use App\Models\Inquiry;
use App\Services\InquiryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Inquiries')] class extends Component
{
 use AuthorizesRequests, WithPagination;

 public string $status = '';
 public string $departmentId = '';
 public string $search = '';

 public function mount(): void
 {
  $this->authorize('viewAny', Inquiry::class);
 }

 public function updated($property): void
 {
  if (in_array($property, ['status', 'departmentId', 'search'], true)) {
   $this->resetPage();
  }
 }

 public function with(InquiryService $inquiries): array
 {
  $user = Auth::user();

  return [
   'inquiries' => $inquiries->listInquiriesForUser($user, [
    'status' => $this->status ?: null,
    'department_id' => $this->departmentId ?: null,
    'search' => $this->search ?: null,
   ]),
   'statuses' => InquiryStatus::cases(),
   'departments' => Department::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name']),
  ];
 }
}; ?>

<div class="flex flex-col gap-8">
 <div class="flex flex-col gap-1">
  <x-page-header
   :title="__('Inquiries')"
   :description="__('Manage lightweight requests and general questions from customers.')"
  >
   <x-slot:actions>
    @can('create', \App\Models\Inquiry::class)
     <flux:button variant="primary" icon="plus":href="route('inquiries.create')" wire:navigate>
      {{ __('New inquiry') }}
     </flux:button>
    @endcan
   </x-slot:actions>
  </x-page-header>
 </div>

 <div class="flex flex-col gap-6">
  {{-- Filters --}}
  <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
   <div class="flex flex-1 flex-wrap items-center gap-3">
    <div class="w-full sm:w-80">
     <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass":placeholder="__('Search inquiry # or subject...')"/>
    </div>

    <div class="flex flex-wrap items-center gap-3">
     <div class="w-44">
      <flux:select wire:model.live="status":placeholder="__('Any status')">
       <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
       @foreach ($statuses as $inquiryStatus)
        <flux:select.option value="{{ $inquiryStatus->value }}">{{ __(str_replace('_', ' ', $inquiryStatus->value)) }}</flux:select.option>
       @endforeach
      </flux:select>
     </div>

     <div class="w-44">
      <flux:select wire:model.live="departmentId":placeholder="__('Any department')">
       <flux:select.option value="">{{ __('All departments') }}</flux:select.option>
       @foreach ($departments as $department)
        <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
       @endforeach
      </flux:select>
     </div>
    </div>
   </div>
  </div>

  {{-- Table view --}}
  <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
   <flux:table>
    <flux:table.columns>
     <flux:table.column class="!pl-6">{{ __('Inquiry') }}</flux:table.column>
     <flux:table.column>{{ __('Status') }}</flux:table.column>
     <flux:table.column>{{ __('Department') }}</flux:table.column>
     <flux:table.column>{{ __('Assignee') }}</flux:table.column>
     <flux:table.column class="!pr-6 text-right">{{ __('Created') }}</flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
     @forelse ($inquiries as $inquiry)
      <flux:table.row :key="$inquiry->id" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50":href="route('inquiries.show', $inquiry)" wire:navigate>
       <flux:table.cell class="!pl-6">
        <div class="flex flex-col gap-1">
         <span class="font-mono text-[10px] font-bold tracking-wider text-zinc-400 uppercase">{{ $inquiry->inquiry_number }}</span>
         <span class="font-bold text-zinc-900 dark:text-white">{{ $inquiry->subject }}</span>
        </div>
       </flux:table.cell>
       <flux:table.cell>
        <x-status-badge :status="$inquiry->status->value"/>
       </flux:table.cell>
       <flux:table.cell>
        <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $inquiry->department?->name ?? __('General') }}</span>
       </flux:table.cell>
       <flux:table.cell>
        <div class="flex items-center gap-2.5">
         @if($inquiry->assignedAgent)
          <flux:avatar :name="$inquiry->assignedAgent->name" size="xs"/>
          <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ $inquiry->assignedAgent->name }}</span>
         @else
          <flux:badge size="sm" variant="subtle" color="zinc" class="font-bold uppercase tracking-wide text-[10px]">{{ __('Unassigned') }}</flux:badge>
         @endif
        </div>
       </flux:table.cell>
       <flux:table.cell class="!pr-6 text-right whitespace-nowrap">
        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500"title="{{ $inquiry->created_at->format('Y-m-d H:i') }}">
         {{ $inquiry->created_at->diffForHumans() }}
        </span>
       </flux:table.cell>
      </flux:table.row>
     @empty
      <flux:table.row>
       <flux:table.cell colspan="5" class="py-20">
        <div class="flex flex-col items-center justify-center gap-4">
         <div class="flex size-16 items-center justify-center rounded-full bg-zinc-50 dark:bg-zinc-800">
          <flux:icon name="question-mark-circle" class="size-8 text-zinc-200 dark:text-zinc-700"/>
         </div>
         <div class="text-center">
          <p class="font-bold text-zinc-900 dark:text-white">{{ __('No inquiries found') }}</p>
          <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('We couldn\'t find any inquiries matching your current filters.') }}</p>
         </div>
        </div>
       </flux:table.cell>
      </flux:table.row>
     @endforelse
    </flux:table.rows>
   </flux:table>
  </div>

  <div class="mt-6">
   {{ $inquiries->links() }}
  </div>
 </div>
</div>

