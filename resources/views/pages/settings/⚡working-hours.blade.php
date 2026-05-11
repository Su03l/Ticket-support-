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

<x-pages::settings.layout :heading="__('Working Hours')":subheading="__('Configure support calendar and SLA business windows')">
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

 <form wire:submit="save" class="space-y-8">
  <flux:card class="space-y-6">
   <div class="grid gap-4">
    @foreach ($dayNames as $day => $label)
     <div wire:key="working-day-{{ $day }}" class="group relative grid items-center gap-6 rounded-2xl border border-zinc-200 p-6 transition-colors hover:bg-zinc-50/50 dark:border-zinc-800 dark:hover:bg-zinc-900/30 sm:grid-cols-[1fr_auto_auto]">
      <div class="flex items-center gap-4">
       <flux:switch wire:model.live="days.{{ $day }}.is_working_day"/>
       <div class="flex flex-col">
        <flux:label class="font-bold !text-zinc-900 dark:!text-white">{{ $label }}</flux:label>
        <flux:text size="xs" variant="subtle">
         {{ $days[$day]['is_working_day'] ? __('Working day') : __('Day off') }}
        </flux:text>
       </div>
      </div>

      <div class="flex items-center gap-3 @if(!$days[$day]['is_working_day']) opacity-30 pointer-events-none grayscale @endif">
       <flux:input type="time" wire:model="days.{{ $day }}.starts_at" size="sm" class="!w-32"/>
       <flux:text variant="subtle" size="sm" class="px-2 font-medium">{{ __('to') }}</flux:text>
       <flux:input type="time" wire:model="days.{{ $day }}.ends_at" size="sm" class="!w-32"/>
      </div>

      <div class="hidden sm:block">
       @if($days[$day]['is_working_day'])
        <flux:badge color="emerald" size="sm" variant="pill" class="uppercase tracking-wider font-bold">{{ __('Active') }}</flux:badge>
       @else
        <flux:badge color="zinc" size="sm" variant="pill" class="uppercase tracking-wider font-bold">{{ __('Closed') }}</flux:badge>
       @endif
      </div>
     </div>
    @endforeach
   </div>

   <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800">
    <flux:button type="submit" variant="primary" icon="check">{{ __('Save Working Hours') }}</flux:button>
   </div>
  </flux:card>
 </form>
</x-pages::settings.layout>
