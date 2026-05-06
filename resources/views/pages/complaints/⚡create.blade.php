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

<div class="mx-auto flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Create complaint') }}</flux:heading>
        <flux:text>{{ __('Submit a sensitive concern for formal review.') }}</flux:text>
    </div>

    <form wire:submit="create" class="flex flex-col gap-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-3">
            <flux:select wire:model="departmentId" :label="__('Department')">
                <flux:select.option value="">{{ __('General') }}</flux:select.option>
                @foreach ($departments as $department)
                    <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="relatedTicketId" :label="__('Related ticket')">
                <flux:select.option value="">{{ __('Optional') }}</flux:select.option>
                @foreach ($tickets as $ticket)
                    <flux:select.option value="{{ $ticket->id }}">{{ $ticket->ticket_number }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="severity" :label="__('Severity')">
                @foreach ($severities as $complaintSeverity)
                    <flux:select.option value="{{ $complaintSeverity->value }}">{{ __($complaintSeverity->value) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:input wire:model="title" :label="__('Title')" />
        <flux:textarea wire:model="description" :label="__('Description')" rows="7" />

        @if ($customFieldDefinitions->isNotEmpty())
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($customFieldDefinitions as $field)
                    <flux:input wire:model="customFields.{{ $field->id }}" :label="$field->label" />
                @endforeach
            </div>
        @endif

        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" :href="route('complaints.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Create complaint') }}</flux:button>
        </div>
    </form>
</div>
