<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('مرحباً بك، :name', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:subheading size="lg">{{ __('كيف يمكننا مساعدتك اليوم؟') }}</flux:subheading>
        </div>
        
        <flux:button href="{{ route('customer.tickets.create') }}" variant="primary" icon="plus">
            {{ __('فتح تذكرة جديدة') }}
        </flux:button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <flux:card>
            <flux:heading>{{ __('إجمالي التذاكر') }}</flux:heading>
            <flux:text class="text-3xl font-bold mt-2 text-zinc-800 dark:text-white">{{ $stats['total'] }}</flux:text>
        </flux:card>
        
        <flux:card>
            <flux:heading>{{ __('تذاكر مفتوحة') }}</flux:heading>
            <flux:text class="text-3xl font-bold mt-2 text-orange-600 dark:text-orange-400">{{ $stats['open'] }}</flux:text>
        </flux:card>

        <flux:card>
            <flux:heading>{{ __('تذاكر مغلقة') }}</flux:heading>
            <flux:text class="text-3xl font-bold mt-2 text-green-600 dark:text-green-400">{{ $stats['closed'] }}</flux:text>
        </flux:card>
    </div>

    <flux:heading size="lg" class="mb-4">{{ __('أحدث تذاكري') }}</flux:heading>
    
    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('رقم التذكرة') }}</flux:table.column>
                <flux:table.column>{{ __('العنوان') }}</flux:table.column>
                <flux:table.column>{{ __('الحالة') }}</flux:table.column>
                <flux:table.column>{{ __('تاريخ الإنشاء') }}</flux:table.column>
            </flux:table.columns>
            
            <flux:table.rows>
                @forelse($recentTickets as $ticket)
                    <!-- سنقوم بتجهيز عرض بيانات الجدول في المرحلة القادمة -->
                @empty
                    <flux:table.row><flux:table.cell colspan="4" class="text-center text-zinc-500">{{ __('لا توجد تذاكر حالياً.') }}</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>