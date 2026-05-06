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

<div class="mx-auto flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Create inquiry') }}</flux:heading>
        <flux:text>{{ __('Ask a question that can be answered quickly or converted later.') }}</flux:text>
    </div>

    <form wire:submit="create" class="flex flex-col gap-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:select wire:model="departmentId" :label="__('Department')">
            <flux:select.option value="">{{ __('General') }}</flux:select.option>
            @foreach ($departments as $department)
                <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model="subject" :label="__('Subject')" />
        <flux:textarea wire:model="body" :label="__('Details')" rows="7" />

        @if ($customFieldDefinitions->isNotEmpty())
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($customFieldDefinitions as $field)
                    <flux:input wire:model="customFields.{{ $field->id }}" :label="$field->label" />
                @endforeach
            </div>
        @endif

        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" :href="route('inquiries.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Create inquiry') }}</flux:button>
        </div>
    </form>
</div>
