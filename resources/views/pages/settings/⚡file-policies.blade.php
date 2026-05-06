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

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('File uploads')" :subheading="__('Configure company attachment limits and visibility rules')">
        @if (auth()->user()->company_id === null)
            <flux:select wire:model.live="companyId" :label="__('Company')" class="mb-6">
                @foreach ($companies as $company)
                    <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <form wire:submit="updatePolicy" class="my-6 w-full space-y-6">
            <flux:textarea wire:model="allowedMimeTypes" :label="__('Allowed MIME types')" rows="7" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="maxFileSizeKb" :label="__('Max file size KB')" type="number" min="1" />
                <flux:input wire:model="maxFilesPerRequest" :label="__('Max files per request')" type="number" min="1" />
            </div>

            <div class="grid gap-3">
                <flux:checkbox wire:model="allowPublicAttachments" :label="__('Allow public attachments')" />
                <flux:checkbox wire:model="allowInternalAttachments" :label="__('Allow internal attachments')" />
            </div>

            <flux:button variant="primary" type="submit">{{ __('Save file policy') }}</flux:button>
        </form>
    </x-pages::settings.layout>
</section>
