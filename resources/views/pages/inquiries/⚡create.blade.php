<?php

use App\Enums\CustomFieldAppliesTo;
use App\Models\Department;
use App\Models\Inquiry;
use App\Services\CustomFieldService;
use App\Services\InquiryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create inquiry')] class extends Component
{
 use AuthorizesRequests;

 public string $departmentId = '';
 public string $subject = '';
 public string $body = '';
 public array $customFields = [];

 public function mount(CustomFieldService $fields): void
 {
  $this->authorize('create', Inquiry::class);

  foreach ($fields->fieldsFor(Auth::user(), CustomFieldAppliesTo::Inquiry) as $field) {
   $this->customFields[$field->id] = null;
  }
 }

 public function create(InquiryService $inquiries, CustomFieldService $fields): void
 {
  $user = Auth::user();

  $validated = $this->validate([
   'departmentId' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $user->company_id)],
   'subject' => ['required', 'string', 'max:255'],
   'body' => ['required', 'string', 'min:5'],
  ] + $fields->validationRules(CustomFieldAppliesTo::Inquiry, $user));

  $inquiry = $inquiries->createInquiry($user, [
   'department_id' => $validated['departmentId'] ? (int) $validated['departmentId'] : null,
   'subject' => $validated['subject'],
   'body' => $validated['body'],
  ]);

  $fields->saveValues($inquiry, $this->customFields);

  $this->redirectRoute('inquiries.show', $inquiry, navigate: true);
 }

 public function with(): array
 {
  return [
   'departments' => Department::query()->where('company_id', Auth::user()->company_id)->orderBy('name')->get(['id', 'name']),
   'customFieldDefinitions' => app(CustomFieldService::class)->fieldsFor(Auth::user(), CustomFieldAppliesTo::Inquiry),
  ];
 }
}; ?>

<div class="mx-auto flex max-w-4xl flex-col gap-8">
 <x-page-header
  :title="__('Ask a Question')"
  :description="__('Need a quick answer? Submit an inquiry and our team will get back to you shortly.')"
  :breadcrumbs="[['label' => __('Inquiries'), 'href' => route('inquiries.index')], ['label' => __('New inquiry')]]"
 />

 <form wire:submit="create" class="flex flex-col gap-6">
  <div class="grid gap-6 lg:grid-cols-3">
   {{-- Main Form Content --}}
   <div class="lg:col-span-2 flex flex-col gap-6">
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
     <div class="flex flex-col gap-5">
      <flux:input 
       wire:model="subject"
       :label="__('Subject')"
       :placeholder="__('What is your question about?')"
       autofocus
      />

      <flux:textarea 
       wire:model="body"
       :label="__('Details')"
       rows="10"
       :placeholder="__('Please describe your request or question in detail...')"
      />
     </div>
    </section>

    @if ($customFieldDefinitions->isNotEmpty())
     <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
      <flux:heading level="2" class="mb-5 flex items-center gap-2">
       <flux:icon name="adjustments-horizontal" class="size-5 text-zinc-400"/>
       {{ __('Additional Details') }}
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
      <flux:icon name="building-office" class="size-5 text-zinc-400"/>
      {{ __('Categorization') }}
     </flux:heading>

     <div class="flex flex-col gap-5">
      <flux:select wire:model="departmentId":label="__('Department')">
       <flux:select.option value="">{{ __('General') }}</flux:select.option>
       @foreach ($departments as $department)
        <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
       @endforeach
      </flux:select>
     </div>
    </section>

    <div class="flex flex-col gap-3">
     <flux:button type="submit" variant="primary" icon="paper-airplane" class="w-full">
      {{ __('Send Inquiry') }}
     </flux:button>
     <flux:button variant="ghost":href="route('inquiries.index')" wire:navigate class="w-full">
      {{ __('Cancel and go back') }}
     </flux:button>
    </div>
   </div>
  </div>
 </form>
</div>

