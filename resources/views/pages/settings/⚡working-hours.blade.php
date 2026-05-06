<?php

use App\Models\Company;
use App\Models\WorkingHour;
use App\Services\WorkingHoursService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Working hours')] class extends Component
{
    public string $companyId = '';

    public array $days = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->can('settings.view'), 403);

        $this->companyId = (string) (Auth::user()->company_id ?? Company::query()->orderBy('name')->value('id') ?? '');
        $this->loadDays();
    }

    public function updatedCompanyId(): void
    {
        $this->loadDays();
    }

    public function save(WorkingHoursService $workingHours): void
    {
        abort_unless(Auth::user()->can('settings.update'), 403);

        $company = Auth::user()->company ?? Company::query()->findOrFail($this->companyId);

        $validated = $this->validate([
            'days' => ['required', 'array', 'size:7'],
            'days.*.is_working_day' => ['required', 'boolean'],
            'days.*.starts_at' => ['required', 'date_format:H:i'],
            'days.*.ends_at' => ['required', 'date_format:H:i'],
        ]);

        foreach ($validated['days'] as $dayOfWeek => $day) {
            $workingHours->upsert($company, (int) $dayOfWeek, $day['starts_at'], $day['ends_at'], (bool) $day['is_working_day']);
        }

        Flux::toast(variant: 'success', text: __('Working hours updated.'));
    }

    public function with(): array
    {
        return [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'dayNames' => [
                0 => __('Sunday'),
                1 => __('Monday'),
                2 => __('Tuesday'),
                3 => __('Wednesday'),
                4 => __('Thursday'),
                5 => __('Friday'),
                6 => __('Saturday'),
            ],
        ];
    }

    private function loadDays(): void
    {
        $company = Auth::user()->company ?? Company::query()->find($this->companyId);
        $existing = $company === null
            ? collect()
            : WorkingHour::query()->where('company_id', $company->id)->get()->keyBy('day_of_week');

        $this->days = collect(range(0, 6))->mapWithKeys(fn (int $day): array => [
            $day => [
                'is_working_day' => (bool) ($existing[$day]->is_working_day ?? ! in_array($day, [5, 6], true)),
                'starts_at' => substr((string) ($existing[$day]->starts_at ?? '08:00'), 0, 5),
                'ends_at' => substr((string) ($existing[$day]->ends_at ?? '17:00'), 0, 5),
            ],
        ])->all();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Working hours')" :subheading="__('Configure support calendar and SLA business windows')">
        @if (auth()->user()->company_id === null)
            <flux:select wire:model.live="companyId" :label="__('Company')" class="mb-6">
                @foreach ($companies as $company)
                    <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <form wire:submit="save" class="space-y-4">
            @foreach ($dayNames as $day => $label)
                <div wire:key="working-day-{{ $day }}" class="grid gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800 sm:grid-cols-[1fr_9rem_9rem] sm:items-center">
                    <flux:checkbox wire:model="days.{{ $day }}.is_working_day" :label="$label" />
                    <flux:input type="time" wire:model="days.{{ $day }}.starts_at" :label="__('Starts')" />
                    <flux:input type="time" wire:model="days.{{ $day }}.ends_at" :label="__('Ends')" />
                </div>
            @endforeach

            <flux:button type="submit" variant="primary" icon="check">{{ __('Save working hours') }}</flux:button>
        </form>
    </x-pages::settings.layout>
</section>
