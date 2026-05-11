<?php

use App\Enums\ReportExportFormat;
use App\Models\ReportTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Report Designer')] class extends Component
{
 public string $name = '';

 public string $format = 'pdf';

 public string $paperSize = 'a4';

 public string $orientation = 'portrait';

 public string $body = '<section style="font-family: Arial, sans-serif; direction: rtl; text-align: right;"><h1>تقرير {{ company.name }}</h1><p>تاريخ التوليد: {{ generated_at }}</p><table border="1"cellpadding="8"cellspacing="0"style="width:100%; border-collapse: collapse;"><tr><th>المؤشر</th><th>القيمة</th></tr><tr><td>المسؤول</td><td>{{ user.name }}</td></tr></table></section>';

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

  flux()->toast(
   text: __('Report template created successfully.'),
   variant: 'success',
  );

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

<div class="flex flex-col gap-8">
 <div class="flex items-center justify-between">
  <div>
   <flux:heading size="xl"level="1">{{ __('Document Architect') }}</flux:heading>
   <flux:text variant="subtle" class="mt-1">{{ __('Craft bespoke document structures for PDF and Excel exports.') }}</flux:text>
  </div>
 </div>

 <div class="grid gap-8 xl:grid-cols-[1fr_22rem]">
  <div class="flex flex-col gap-6">
   <form wire:submit="save" class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
    <div class="bg-zinc-50/50 p-6 border-b border-zinc-100 dark:bg-zinc-950/20 dark:border-zinc-800">
     <div class="grid gap-6 md:grid-cols-2">
      <flux:field>
       <flux:label>{{ __('Template Designation') }}</flux:label>
       <flux:input wire:model="name"placeholder="{{ __('e.g. Executive Quarterly Audit') }}"/>
       <flux:error name="name"/>
      </flux:field>

      <div class="grid grid-cols-3 gap-3">
       <flux:field>
        <flux:label>{{ __('Format') }}</flux:label>
        <flux:select wire:model="format">
         <flux:select.option value="pdf">PDF</flux:select.option>
         <flux:select.option value="excel">Excel</flux:select.option>
        </flux:select>
       </flux:field>
       <flux:field>
        <flux:label>{{ __('Size') }}</flux:label>
        <flux:select wire:model="paperSize">
         <flux:select.option value="a4">A4</flux:select.option>
         <flux:select.option value="letter">Letter</flux:select.option>
        </flux:select>
       </flux:field>
       <flux:field>
        <flux:label>{{ __('Layout') }}</flux:label>
        <flux:select wire:model="orientation">
         <flux:select.option value="portrait">{{ __('Port.') }}</flux:select.option>
         <flux:select.option value="landscape">{{ __('Land.') }}</flux:select.option>
        </flux:select>
       </flux:field>
      </div>
     </div>
    </div>

    <div class="p-6">
     <div class="flex flex-col gap-4">
      <flux:label>{{ __('Visual Composer') }}</flux:label>
      <div
       x-data="{
        html: $wire.entangle('body').live,
        command(name, value = null) {
         if (name === 'insertTable') {
          value = '<table border=\'1\' cellpadding=\'8\' cellspacing=\'0\' style=\'width:100%;border-collapse:collapse\'><thead><tr style=\'background:#f4f4f5\'><th>العنوان</th><th>القيمة</th></tr></thead><tbody><tr><td>مثال</td><td>123</td></tr></tbody></table>';
          name = 'insertHTML';
         }
         document.execCommand(name, false, value)
         this.html = this.$refs.editor.innerHTML
        },
       }"
       class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800 focus-within:ring-2 focus-within:ring-blue-500/20"
      >
       <div class="flex flex-wrap items-center gap-1 border-b border-zinc-200 bg-zinc-50/50 p-2 dark:border-zinc-800 dark:bg-zinc-950">
        <flux:button type="button" size="sm" variant="ghost"x-on:click="command('bold')" class="font-bold">B</flux:button>
        <flux:button type="button" size="sm" variant="ghost"x-on:click="command('italic')" class="italic">I</flux:button>
        <flux:separator vertical class="mx-1 h-4"/>
        <flux:button type="button" size="sm" variant="ghost" icon="list-bullet"x-on:click="command('insertUnorderedList')"/>
        <flux:button type="button" size="sm" variant="ghost" icon="numbered-list"x-on:click="command('insertOrderedList')"/>
        <flux:separator vertical class="mx-1 h-4"/>
        <flux:button type="button" size="sm" variant="ghost" icon="table-cells"x-on:click="command('insertTable')"/>
        <flux:separator vertical class="mx-1 h-4"/>
        <flux:button type="button" size="sm" variant="ghost" icon="chevron-left"x-on:click="command('justifyRight')"/>
        <flux:button type="button" size="sm" variant="ghost" icon="bars-3"x-on:click="command('justifyCenter')"/>
        <flux:button type="button" size="sm" variant="ghost" icon="chevron-right"x-on:click="command('justifyLeft')"/>
       </div>
       <div
        x-ref="editor"
        contenteditable="true"
        dir="rtl"
        x-html="html"
        x-on:input="html = $event.target.innerHTML"
        class="min-h-[500px] overflow-auto bg-white p-12 text-sm leading-relaxed outline-none dark:bg-zinc-900 prose prose-zinc dark:prose-invert max-w-none"
       ></div>
      </div>
     </div>

     <div class="mt-8 border-t border-zinc-100 pt-8 dark:border-zinc-800">
      <flux:field>
       <div class="flex items-center justify-between mb-2">
        <flux:label>{{ __('Markup Source') }}</flux:label>
        <flux:badge size="sm" color="zinc" variant="subtle">{{ __('Advanced') }}</flux:badge>
       </div>
       <flux:textarea wire:model="body"rows="6" class="font-mono text-xs bg-zinc-50 dark:bg-zinc-950/40"/>
      </flux:field>
     </div>

     <div class="mt-8 flex justify-end">
      <flux:button type="submit" variant="primary" icon="plus">{{ __('Finalize & Save Template') }}</flux:button>
     </div>
    </div>
   </form>
  </div>

  <aside class="flex flex-col gap-6">
   <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading size="md" class="mb-5">{{ __('Template Library') }}</flux:heading>
    <div class="space-y-3">
     @forelse ($templates as $template)
      <div wire:key="template-{{ $template->id }}" class="group relative flex flex-col gap-3 rounded-xl border border-zinc-100 p-4 transition-all hover:border-zinc-200 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/50">
       <div class="flex items-start justify-between">
        <div class="min-w-0">
         <flux:heading size="sm" class="truncate font-semibold">{{ $template->name }}</flux:heading>
         <flux:text size="xs" variant="subtle">{{ $template->created_at->diffForHumans() }}</flux:text>
        </div>
        <flux:badge size="xs" color="blue" variant="subtle" class="font-bold uppercase tracking-widest">{{ $template->format->value }}</flux:badge>
       </div>
       <div class="flex gap-2">
        <flux:button size="xs" variant="outline" icon="arrow-down-tray":href="route('reports.templates.export', $template)" class="flex-1">
         {{ __('Sample') }}
        </flux:button>
       </div>
      </div>
     @empty
      <div class="py-12 text-center">
       <flux:icon icon="document-duplicate" class="mx-auto mb-3 size-10 text-zinc-300"/>
       <flux:text size="sm" variant="subtle">{{ __('No templates in library.') }}</flux:text>
      </div>
     @endforelse
    </div>
   </div>

   <div class="rounded-2xl bg-zinc-900 p-6 text-white dark:bg-zinc-800">
    <flux:heading size="md" class="text-white mb-4">{{ __('Merge Tags') }}</flux:heading>
    <flux:text size="sm" class="mb-6 text-zinc-400 leading-relaxed">{{ __('Inject dynamic operational data into your documents using these placeholders.') }}</flux:text>
    <div class="space-y-2">
     @foreach (['company.name', 'user.name', 'generated_at', 'report.date_range'] as $var)
      @php($placeholder = '{{ '.$var.' }}')
      <div class="group flex items-center justify-between rounded-lg bg-zinc-800 px-3 py-2 transition-colors hover:bg-zinc-700"data-placeholder="{{ $placeholder }}">
       <code class="text-xs font-mono text-blue-400">{{ $placeholder }}</code>
       <flux:button icon="clipboard" variant="ghost" size="xs" class="text-zinc-500 hover:text-white"x-on:click="navigator.clipboard.writeText($el.closest('[data-placeholder]').dataset.placeholder)"/>
      </div>
     @endforeach
    </div>
   </div>
  </aside>
 </div>
</div>
