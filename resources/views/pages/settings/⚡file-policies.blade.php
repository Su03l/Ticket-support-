<?php

use App\Models\FileUploadPolicy;
use App\Models\Company;
use App\Services\FileUploadPolicyService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('File upload policies')] class extends Component
{
 use AuthorizesRequests;

 public FileUploadPolicy $policy;

 public string $companyId = '';

 public string $allowedMimeTypes = '';

 public int $maxFileSizeKb = 10240;

 public ?int $maxFilesPerRequest = 5;

 public bool $allowPublicAttachments = true;

 public bool $allowInternalAttachments = true;

 public function mount(FileUploadPolicyService $policies): void
 {
  $company = Auth::user()->company ?? Company::query()->orderBy('name')->first();

  abort_if($company === null, 403);

  $this->companyId = (string) $company->id;
  $this->loadPolicy($policies);
 }

 public function updatedCompanyId(): void
 {
  $this->loadPolicy(app(FileUploadPolicyService::class));
 }

 private function loadPolicy(FileUploadPolicyService $policies): void
 {
  $company = Auth::user()->company ?? Company::query()->findOrFail($this->companyId);
  $this->policy = $policies->policyFor($company);
  $this->authorize('view', $this->policy);

  $this->allowedMimeTypes = implode("\n", $this->policy->allowed_mime_types ?? []);
  $this->maxFileSizeKb = $this->policy->max_file_size_kb;
  $this->maxFilesPerRequest = $this->policy->max_files_per_request;
  $this->allowPublicAttachments = $this->policy->allow_public_attachments;
  $this->allowInternalAttachments = $this->policy->allow_internal_attachments;
 }

 public function updatePolicy(FileUploadPolicyService $policies): void
 {
  $this->authorize('update', $this->policy);

  $validated = $this->validate([
   'allowedMimeTypes' => ['required', 'string', 'max:2000'],
   'maxFileSizeKb' => ['required', 'integer', 'min:1', 'max:102400'],
   'maxFilesPerRequest' => ['nullable', 'integer', 'min:1', 'max:50'],
   'allowPublicAttachments' => ['required', 'boolean'],
   'allowInternalAttachments' => ['required', 'boolean'],
  ]);

  $this->policy = $policies->update($this->policy, [
   'allowed_mime_types' => $validated['allowedMimeTypes'],
   'max_file_size_kb' => $validated['maxFileSizeKb'],
   'max_files_per_request' => $validated['maxFilesPerRequest'],
   'allow_public_attachments' => $validated['allowPublicAttachments'],
   'allow_internal_attachments' => $validated['allowInternalAttachments'],
  ]);

  Flux::toast(variant: 'success', text: __('File policy updated.'));
 }

 public function with(): array
 {
  return [
   'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
  ];
 }
}; ?>

<x-pages::settings.layout :heading="__('File Policies')":subheading="__('Configure company attachment limits and visibility rules')">
 @if (auth()->user()->company_id === null)
  <flux:card class="mb-6">
   <div class="max-w-md">
    <flux:select wire:model.live="companyId":label="__('Target Company')" icon="building-office">
     @foreach ($companies as $company)
      <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
     @endforeach
    </flux:select>
   </div>
  </flux:card>
 @endif

 <form wire:submit="updatePolicy" class="space-y-8">
  {{-- Allowed Formats Card --}}
  <flux:card class="space-y-6">
   <div>
    <flux:heading size="lg">{{ __('Allowed formats') }}</flux:heading>
    <flux:subheading>{{ __('Define which MIME types are permitted for uploads. Enter one per line.') }}</flux:subheading>
   </div>

   <div class="max-w-2xl">
    <flux:textarea wire:model="allowedMimeTypes":label="__('MIME types list')"rows="8"placeholder="e.g. image/jpeg&#10;application/pdf"/>
   </div>
  </flux:card>

  {{-- Capacity & Limits Card --}}
  <flux:card class="space-y-6">
   <div>
    <flux:heading size="lg">{{ __('Capacity & Limits') }}</flux:heading>
    <flux:subheading>{{ __('Set restrictions on file sizes and the number of attachments per request.') }}</flux:subheading>
   </div>

   <div class="grid gap-6 sm:grid-cols-2">
    <flux:input wire:model="maxFileSizeKb":label="__('Max file size (KB)')" type="number"min="1"step="1024" icon="archive-box"/>
    <flux:input wire:model="maxFilesPerRequest":label="__('Max files per request')" type="number"min="1" icon="squares-plus"/>
   </div>
  </flux:card>

  {{-- Visibility & Privacy Card --}}
  <flux:card class="space-y-6">
   <div>
    <flux:heading size="lg">{{ __('Visibility & Privacy') }}</flux:heading>
    <flux:subheading>{{ __('Control who can view attachments across public and internal channels.') }}</flux:subheading>
   </div>

   <div class="grid gap-4 rounded-2xl border border-zinc-200 p-8 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-900/10">
    <flux:checkbox wire:model="allowPublicAttachments":label="__('Allow public attachments')"description="{{ __('When enabled, customers can view and download attachments on their tickets.') }}"/>
    <flux:separator class="my-4 !border-zinc-100 dark:!border-zinc-900"/>
    <flux:checkbox wire:model="allowInternalAttachments":label="__('Allow internal attachments')"description="{{ __('Control visibility for staff-only internal attachments and comments.') }}"/>
   </div>

   <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800">
    <flux:button variant="primary" type="submit" icon="check">{{ __('Save File Policy') }}</flux:button>
   </div>
  </flux:card>
 </form>
</x-pages::settings.layout>
