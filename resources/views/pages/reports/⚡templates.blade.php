<?php

use App\Enums\ReportExportFormat;
use App\Models\ReportTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Report designer')] class extends Component
{
    public string $name = '';

    public string $format = 'pdf';

    public string $paperSize = 'a4';

    public string $orientation = 'portrait';

    public string $body = '<section style="font-family: Arial, sans-serif; direction: rtl; text-align: right;"><h1>تقرير {{ company.name }}</h1><p>تاريخ التوليد: {{ generated_at }}</p><table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse: collapse;"><tr><th>المؤشر</th><th>القيمة</th></tr><tr><td>المسؤول</td><td>{{ user.name }}</td></tr></table></section>';

    public function mount(): void
    {
        abort_unless(Auth::user()->can('reports.export'), 403);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'format' => ['required', Rule::enum(ReportExportFormat::class)],
            'paperSize' => ['required', 'in:a4,letter'],
            'orientation' => ['required', 'in:portrait,landscape'],
            'body' => ['required', 'string', 'min:10'],
        ]);

        ReportTemplate::query()->create([
            'company_id' => Auth::user()->company_id,
            'created_by_id' => Auth::id(),
            'name' => $validated['name'],
            'format' => ReportExportFormat::from($validated['format']),
            'paper_size' => $validated['paperSize'],
            'orientation' => $validated['orientation'],
            'body' => $validated['body'],
        ]);

        $this->reset(['name']);
    }

    public function with(): array
    {
        return [
            'templates' => ReportTemplate::query()
                ->where('company_id', Auth::user()->company_id)
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
    <form wire:submit="save" class="flex flex-col gap-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <flux:heading size="xl">{{ __('Report designer') }}</flux:heading>
            <flux:text>{{ __('Design Arabic PDF and Excel templates inside the application.') }}</flux:text>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
            <flux:input wire:model="name" :label="__('Name')" />
            <flux:select wire:model="format" :label="__('Format')">
                <flux:select.option value="pdf">{{ __('PDF') }}</flux:select.option>
                <flux:select.option value="excel">{{ __('Excel') }}</flux:select.option>
            </flux:select>
            <flux:select wire:model="paperSize" :label="__('Paper')">
                <flux:select.option value="a4">A4</flux:select.option>
                <flux:select.option value="letter">Letter</flux:select.option>
            </flux:select>
            <flux:select wire:model="orientation" :label="__('Orientation')">
                <flux:select.option value="portrait">{{ __('Portrait') }}</flux:select.option>
                <flux:select.option value="landscape">{{ __('Landscape') }}</flux:select.option>
            </flux:select>
        </div>

        <div
            x-data="{
                html: @entangle('body').live,
                command(name, value = null) {
                    document.execCommand(name, false, value)
                    this.html = this.$refs.editor.innerHTML
                },
            }"
            class="rounded-lg border border-zinc-200 dark:border-zinc-800"
        >
            <div class="flex flex-wrap gap-2 border-b border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-800 dark:bg-zinc-950">
                <flux:button type="button" size="sm" x-on:click="command('bold')"><strong>B</strong></flux:button>
                <flux:button type="button" size="sm" x-on:click="command('italic')"><em>I</em></flux:button>
                <flux:button type="button" size="sm" icon="list-bullet" x-on:click="command('insertUnorderedList')" />
                <flux:button type="button" size="sm" icon="numbered-list" x-on:click="command('insertOrderedList')" />
                <flux:button type="button" size="sm" icon="table-cells" x-on:click="command('insertHTML', '<table border=&quot;1&quot; cellpadding=&quot;8&quot; cellspacing=&quot;0&quot; style=&quot;width:100%;border-collapse:collapse&quot;><tr><th>العنوان</th><th>القيمة</th></tr><tr><td>مثال</td><td>123</td></tr></table>')" />
            </div>
            <div
                x-ref="editor"
                contenteditable="true"
                dir="rtl"
                x-html="html"
                x-on:input="html = $event.target.innerHTML"
                class="min-h-80 overflow-auto bg-white p-4 text-sm leading-7 outline-none dark:bg-zinc-900"
            ></div>
        </div>

        <flux:textarea wire:model="body" :label="__('Template HTML')" rows="8" />

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" icon="document-plus">{{ __('Save template') }}</flux:button>
        </div>
    </form>

    <div class="flex flex-col gap-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="sm">{{ __('Saved templates') }}</flux:heading>
            <div class="mt-4 flex flex-col gap-3">
                @forelse ($templates as $template)
                    <div wire:key="template-{{ $template->id }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                        <div class="flex items-center justify-between gap-2">
                            <flux:heading size="sm">{{ $template->name }}</flux:heading>
                            <flux:badge size="sm">{{ strtoupper($template->format->value) }}</flux:badge>
                        </div>
                        <flux:button class="mt-3 w-full" size="sm" icon="arrow-down-tray" :href="route('reports.templates.export', $template)">
                            {{ __('Export') }}
                        </flux:button>
                    </div>
                @empty
                    <flux:text>{{ __('No templates yet.') }}</flux:text>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="sm">{{ __('Variables') }}</flux:heading>
            <div class="mt-3 grid gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <code>{{ '{{ company.name }}' }}</code>
                <code>{{ '{{ user.name }}' }}</code>
                <code>{{ '{{ generated_at }}' }}</code>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="sm">{{ __('HTML preview') }}</flux:heading>
            <div class="mt-3 max-h-96 overflow-auto rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                <pre class="whitespace-pre-wrap break-words">{{ $body }}</pre>
            </div>
        </div>
    </div>
</div>
