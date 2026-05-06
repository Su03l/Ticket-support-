<?php

use App\Enums\CustomFieldAppliesTo;
use App\Enums\CustomFieldType;
use App\Models\CustomField;
use App\Services\CustomFieldService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Custom fields')] class extends Component
{
    use AuthorizesRequests;
    public string $label = '';
    public string $appliesTo = 'ticket';
    public string $type = 'text';

    public function mount(): void { $this->authorize('viewAny', CustomField::class); }

    public function create(CustomFieldService $fields): void
    {
        $this->authorize('create', CustomField::class);
        $validated = $this->validate(['label' => ['required', 'max:255'], 'appliesTo' => ['required', Rule::enum(CustomFieldAppliesTo::class)], 'type' => ['required', Rule::enum(CustomFieldType::class)]]);
        $fields->create(Auth::user(), ['label' => $validated['label'], 'applies_to' => $validated['appliesTo'], 'type' => $validated['type']]);
        $this->reset('label');
    }

    public function with(): array
    {
        return ['fields' => CustomField::query()->where('company_id', Auth::user()->company_id)->orderBy('sort_order')->get(), 'appliesToOptions' => CustomFieldAppliesTo::cases(), 'types' => CustomFieldType::cases()];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div><flux:heading size="xl">{{ __('Custom fields') }}</flux:heading><flux:text>{{ __('Configure dynamic fields for tickets, complaints, inquiries, and customers.') }}</flux:text></div>
    @can('create', \App\Models\CustomField::class)
        <form wire:submit="create" class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 md:grid-cols-4">
            <flux:input wire:model="label" :label="__('Label')" />
            <flux:select wire:model="appliesTo" :label="__('Applies to')">@foreach($appliesToOptions as $option)<flux:select.option value="{{ $option->value }}">{{ __(str_replace('_', ' ', $option->value)) }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model="type" :label="__('Type')">@foreach($types as $fieldType)<flux:select.option value="{{ $fieldType->value }}">{{ __(str_replace('_', ' ', $fieldType->value)) }}</flux:select.option>@endforeach</flux:select>
            <flux:button type="submit" variant="primary" class="self-end">{{ __('Save') }}</flux:button>
        </form>
    @endcan
    <div class="grid gap-3 md:grid-cols-2">
        @foreach ($fields as $field)
            <div wire:key="custom-field-{{ $field->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="sm">{{ $field->label }}</flux:heading>
                <flux:text>{{ __(str_replace('_', ' ', $field->applies_to->value)) }} &middot; {{ __(str_replace('_', ' ', $field->type->value)) }}</flux:text>
            </div>
        @endforeach
    </div>
</div>
