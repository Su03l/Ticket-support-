<?php

use App\Enums\ComplaintSeverity;
use App\Enums\CustomFieldAppliesTo;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Ticket;
use App\Services\ComplaintService;
use App\Services\CustomFieldService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create complaint')] class extends Component
{
 use AuthorizesRequests;

 public string $departmentId = '';

 public string $relatedTicketId = '';

 public string $severity = 'medium';

 public string $title = '';

 public string $description = '';

 public array $customFields = [];

 public function mount(CustomFieldService $fields): void
 {
  $this->authorize('create', Complaint::class);

  foreach ($fields->fieldsFor(Auth::user(), CustomFieldAppliesTo::Complaint) as $field) {
   $this->customFields[$field->id] = null;
  }
 }

 public function create(ComplaintService $complaints, CustomFieldService $fields): void
 {
  $user = Auth::user();

  $validated = $this->validate([
   'departmentId' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $user->company_id)],
   'relatedTicketId' => ['nullable', Rule::exists('tickets', 'id')->where('company_id', $user->company_id)->where('customer_id', $user->id)],
   'severity' => ['required', Rule::enum(ComplaintSeverity::class)],
   'title' => ['required', 'string', 'max:255'],
   'description' => ['required', 'string', 'min:10'],
  ] + $fields->validationRules(CustomFieldAppliesTo::Complaint, $user));

  $complaint = $complaints->createComplaint($user, [
   'department_id' => $validated['departmentId'] ? (int) $validated['departmentId'] : null,
   'related_ticket_id' => $validated['relatedTicketId'] ? (int) $validated['relatedTicketId'] : null,
   'severity' => $validated['severity'],
   'title' => $validated['title'],
   'description' => $validated['description'],
  ]);

  $fields->saveValues($complaint, $this->customFields);

  $this->redirectRoute('complaints.show', $complaint, navigate: true);
 }

 public function with(): array
 {
  $user = Auth::user();

  return [
   'departments' => Department::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name']),
   'tickets' => Ticket::query()->where('company_id', $user->company_id)->where('customer_id', $user->id)->latest()->get(['id', 'ticket_number', 'title']),
   'severities' => ComplaintSeverity::cases(),
   'customFieldDefinitions' => app(CustomFieldService::class)->fieldsFor($user, CustomFieldAppliesTo::Complaint),
  ];
 }
}; ?>

<div class="mx-auto flex max-w-4xl flex-col gap-8">
 <x-page-header
  :title="__('Submit Formal Complaint')"
  :description="__('Please provide detailed information about your concern for a formal review.')"
  :breadcrumbs="[['label' => __('Complaints'), 'href' => route('complaints.index')], ['label' => __('New complaint')]]"
 />

 <form wire:submit="create" class="flex flex-col gap-6">
  <div class="grid gap-6 lg:grid-cols-3">
   {{-- Main Form Content --}}
   <div class="lg:col-span-2 flex flex-col gap-6">
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
     <div class="flex flex-col gap-5">
      <flux:input 
       wire:model="title"
       :label="__('Complaint Title')"
       :placeholder="__('Briefly describe the nature of your complaint')"
       autofocus
      />

      <flux:textarea 
       wire:model="description"
       :label="__('Detailed Description')"
       rows="10"
       :placeholder="__('Explain the situation in detail, including dates and any relevant facts...')"
      />
     </div>
    </section>

    @if ($customFieldDefinitions->isNotEmpty())
     <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
      <flux:heading level="2" class="mb-5 flex items-center gap-2">
       <flux:icon name="adjustments-horizontal" class="size-5 text-zinc-400"/>
       {{ __('Additional Information') }}
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
      <flux:icon name="shield-check" class="size-5 text-zinc-400"/>
      {{ __('Categorization') }}
     </flux:heading>

     <div class="flex flex-col gap-5">
      <flux:select wire:model="severity":label="__('Severity Level')">
       @foreach ($severities as $complaintSeverity)
        <flux:select.option value="{{ $complaintSeverity->value }}">{{ __($complaintSeverity->value) }}</flux:select.option>
       @endforeach
      </flux:select>

      <flux:select wire:model="departmentId":label="__('Target Department')">
       <flux:select.option value="">{{ __('General / No Department') }}</flux:select.option>
       @foreach ($departments as $department)
        <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
       @endforeach
      </flux:select>

      <flux:select wire:model="relatedTicketId":label="__('Related Ticket')">
       <flux:select.option value="">{{ __('None / Not Applicable') }}</flux:select.option>
       @foreach ($tickets as $ticket)
        <flux:select.option value="{{ $ticket->id }}">{{ $ticket->ticket_number }} - {{ $ticket->title }}</flux:select.option>
       @endforeach
      </flux:select>
     </div>
    </section>

    <div class="flex flex-col gap-3">
     <flux:button type="submit" variant="primary" icon="paper-airplane" class="w-full">
      {{ __('Submit Complaint') }}
     </flux:button>
     <flux:button variant="ghost":href="route('complaints.index')" wire:navigate class="w-full">
      {{ __('Cancel and go back') }}
     </flux:button>
    </div>
   </div>
  </div>
 </form>
</div>

