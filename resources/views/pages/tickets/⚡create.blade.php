<?php

use App\Enums\CustomFieldAppliesTo;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Services\CustomFieldService;
use App\Services\TicketService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create ticket')] class extends Component
{
 use AuthorizesRequests;

 public string $departmentId = '';

 public string $categoryId = '';

 public string $priorityId = '';

 public string $title = '';

 public string $description = '';

 public array $customFields = [];

 public function mount(CustomFieldService $fields): void
 {
  $this->authorize('create', Ticket::class);

  foreach ($fields->fieldsFor(Auth::user(), CustomFieldAppliesTo::Ticket) as $field) {
   $this->customFields[$field->id] = null;
  }
 }

 public function create(TicketService $tickets, CustomFieldService $fields): void
 {
  $user = Auth::user();

  $validated = $this->validate([
   'departmentId' => ['required', Rule::exists('departments', 'id')->where('company_id', $user->company_id)],
   'categoryId' => ['nullable', Rule::exists('ticket_categories', 'id')->where('company_id', $user->company_id)],
   'priorityId' => ['nullable', Rule::exists('ticket_priorities', 'id')->where('company_id', $user->company_id)],
   'title'  => ['required', 'string', 'max:255'],
   'description' => ['required', 'string', 'min:10'],
  ] + $fields->validationRules(CustomFieldAppliesTo::Ticket, $user));

  $ticket = $tickets->createTicket($user, [
   'department_id' => (int) $validated['departmentId'],
   'category_id' => $validated['categoryId'] ? (int) $validated['categoryId'] : null,
   'priority_id' => $validated['priorityId'] ? (int) $validated['priorityId'] : null,
   'title'   => $validated['title'],
   'description' => $validated['description'],
  ]);

  $fields->saveValues($ticket, $this->customFields);

  $this->redirectRoute('tickets.show', $ticket, navigate: true);
 }

 public function with(): array
 {
  $companyId = Auth::user()->company_id;

  return [
   'departments'   => Department::query()->where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
   'categories'   => TicketCategory::query()->where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
   'priorities'   => TicketPriority::query()->where('company_id', $companyId)->where('is_active', true)->orderBy('level')->get(['id', 'name']),
   'customFieldDefinitions' => app(CustomFieldService::class)->fieldsFor(Auth::user(), CustomFieldAppliesTo::Ticket),
  ];
 }
}; ?>

<div class="mx-auto flex max-w-4xl flex-col gap-8">
 <x-page-header
  :title="__('Create new ticket')"
  :description="__('Please fill in the details below to submit a support request.')"
  :breadcrumbs="[['label' => __('Tickets'), 'href' => route('tickets.index')], ['label' => __('New ticket')]]"
 />

 <form wire:submit="create" class="flex flex-col gap-6">
  <div class="grid gap-6 lg:grid-cols-3">
   {{-- Main Form Content --}}
   <div class="lg:col-span-2 flex flex-col gap-6">
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
     <div class="flex flex-col gap-5">
      <flux:input 
       wire:model="title"
       :label="__('Subject')"
       :placeholder="__('What is this ticket about?')"
       autofocus
      />

      <flux:textarea 
       wire:model="description"
       :label="__('Description')"
       rows="10"
       :placeholder="__('Please provide as much detail as possible...')"
      />
     </div>
    </section>

    @if ($customFieldDefinitions->isNotEmpty())
     <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
      <flux:heading level="2" class="mb-5 flex items-center gap-2">
       <flux:icon name="adjustments-horizontal" class="size-5 text-zinc-400"/>
       {{ __('Additional details') }}
      </flux:heading>

      <div class="grid gap-5 sm:grid-cols-2">
       @foreach ($customFieldDefinitions as $field)
        <div class="@if($field->type === \App\Enums\CustomFieldType::Textarea) sm:col-span-2 @endif">
         @if ($field->type === \App\Enums\CustomFieldType::Textarea)
          <flux:textarea wire:model="customFields.{{ $field->id }}":label="$field->label"rows="3"/>
         @else
          <flux:input wire:model="customFields.{{ $field->id }}":label="$field->label"/>
         @endif
        </div>
       @endforeach
      </div>
     </section>
    @endif
   </div>

   {{-- Sidebar Options --}}
   <div class="flex flex-col gap-6">
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
     <flux:heading level="2" class="mb-5 flex items-center gap-2">
      <flux:icon name="list-bullet" class="size-5 text-zinc-400"/>
      {{ __('Categorization') }}
     </flux:heading>

     <div class="flex flex-col gap-5">
      <flux:select wire:model="departmentId":label="__('Department')">
       <flux:select.option value="">{{ __('Select department') }}</flux:select.option>
       @foreach ($departments as $department)
        <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
       @endforeach
      </flux:select>

      <flux:select wire:model="categoryId":label="__('Category')">
       <flux:select.option value="">{{ __('Select category (Optional)') }}</flux:select.option>
       @foreach ($categories as $category)
        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
       @endforeach
      </flux:select>

      <flux:select wire:model="priorityId":label="__('Priority')">
       <flux:select.option value="">{{ __('Select priority (Optional)') }}</flux:select.option>
       @foreach ($priorities as $priority)
        <flux:select.option value="{{ $priority->id }}">{{ $priority->name }}</flux:select.option>
       @endforeach
      </flux:select>
     </div>
    </section>

    <div class="flex flex-col gap-3">
     <flux:button type="submit" variant="primary" icon="paper-airplane" class="w-full">
      {{ __('Create ticket') }}
     </flux:button>
     <flux:button variant="ghost":href="route('tickets.index')" wire:navigate class="w-full">
      {{ __('Cancel and go back') }}
     </flux:button>
    </div>
   </div>
  </div>
 </form>
</div>

