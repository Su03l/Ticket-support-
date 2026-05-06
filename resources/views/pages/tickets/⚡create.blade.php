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
            'categoryId'   => ['nullable', Rule::exists('ticket_categories', 'id')->where('company_id', $user->company_id)],
            'priorityId'   => ['nullable', Rule::exists('ticket_priorities', 'id')->where('company_id', $user->company_id)],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'min:10'],
        ] + $fields->validationRules(CustomFieldAppliesTo::Ticket, $user));

        $ticket = $tickets->createTicket($user, [
            'department_id' => (int) $validated['departmentId'],
            'category_id'   => $validated['categoryId'] ? (int) $validated['categoryId'] : null,
            'priority_id'   => $validated['priorityId'] ? (int) $validated['priorityId'] : null,
            'title'         => $validated['title'],
            'description'   => $validated['description'],
        ]);

        $fields->saveValues($ticket, $this->customFields);

        $this->redirectRoute('tickets.show', $ticket, navigate: true);
    }

    public function with(): array
    {
        $companyId = Auth::user()->company_id;

        return [
            'departments'          => Department::query()->where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'categories'           => TicketCategory::query()->where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'priorities'           => TicketPriority::query()->where('company_id', $companyId)->where('is_active', true)->orderBy('level')->get(['id', 'name']),
            'customFieldDefinitions' => app(CustomFieldService::class)->fieldsFor(Auth::user(), CustomFieldAppliesTo::Ticket),
        ];
    }
}; ?>

<div class="mx-auto flex max-w-3xl flex-col gap-6">
    <x-page-header
        :title="__('Create ticket')"
        :description="__('Submit a support request to the right department.')"
        :breadcrumbs="[['label' => __('Tickets'), 'href' => route('tickets.index')], ['label' => __('New ticket')]]"
    />

    <x-section-card :heading="__('Ticket details')" icon="ticket">
        <form wire:submit="create" class="flex flex-col gap-5">
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model="departmentId" :label="__('Department')">
                    <flux:select.option value="">{{ __('Select department') }}</flux:select.option>
                    @foreach ($departments as $department)
                        <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="categoryId" :label="__('Category')">
                    <flux:select.option value="">{{ __('Optional') }}</flux:select.option>
                    @foreach ($categories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="priorityId" :label="__('Priority')">
                    <flux:select.option value="">{{ __('Optional') }}</flux:select.option>
                    @foreach ($priorities as $priority)
                        <flux:select.option value="{{ $priority->id }}">{{ $priority->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model="title" :label="__('Title')" :placeholder="__('Brief summary of the issue')" />
            <flux:textarea wire:model="description" :label="__('Description')" rows="7" :placeholder="__('Describe the issue in detail...')" />

            @if ($customFieldDefinitions->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($customFieldDefinitions as $field)
                        @if ($field->type === \App\Enums\CustomFieldType::Textarea)
                            <flux:textarea wire:model="customFields.{{ $field->id }}" :label="$field->label" rows="3" />
                        @else
                            <flux:input wire:model="customFields.{{ $field->id }}" :label="$field->label" />
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="flex items-center justify-end gap-3 border-t border-zinc-100/80 pt-4 dark:border-zinc-800">
                <flux:button variant="ghost" :href="route('tickets.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('Create ticket') }}</flux:button>
            </div>
        </form>
    </x-section-card>
</div>
