<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ __('تذكرة دعم فني جديدة') }}</flux:heading>
        <flux:subheading>{{ __('يرجى تعبئة التفاصيل أدناه بدقة لنتمكن من مساعدتك بشكل أفضل.') }}</flux:subheading>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            
            <flux:input 
                wire:model="subject" 
                label="{{ __('عنوان التذكرة') }}" 
                placeholder="{{ __('مثال: مشكلة في تسجيل الدخول') }}" 
                required 
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:select wire:model="department_id" label="{{ __('القسم المختص') }}" required>
                    <option value="" disabled>{{ __('اختر القسم...') }}</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="priority" label="{{ __('الأولوية') }}" required>
                    <option value="low">{{ __('منخفضة (استفسار عادي)') }}</option>
                    <option value="medium">{{ __('متوسطة') }}</option>
                    <option value="high">{{ __('عالية (مشكلة تؤثر على العمل)') }}</option>
                    <option value="urgent">{{ __('عاجلة جداً (توقف النظام)') }}</option>
                </flux:select>
            </div>

            <flux:textarea 
                wire:model="description" 
                label="{{ __('وصف المشكلة') }}" 
                rows="6" 
                placeholder="{{ __('الرجاء شرح المشكلة بالتفصيل لتسريع عملية الدعم...') }}" 
                required 
            />

            <flux:input type="file" wire:model="attachments" multiple label="{{ __('المرفقات (اختياري)') }}" description="{{ __('يمكنك رفع صور أو ملفات توضح المشكلة.') }}" />

            <div class="flex justify-end gap-3 mt-6">
                <flux:button href="{{ route('customer.dashboard') }}" variant="ghost">{{ __('إلغاء') }}</flux:button>
                <flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('إرسال التذكرة') }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>