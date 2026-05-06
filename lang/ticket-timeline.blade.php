<div class="space-y-6">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Status History') }}</h3>
    
    <div class="relative border-s border-gray-200 dark:border-gray-700 ms-3">
        @forelse($histories as $history)
            <div class="mb-6 ms-6">
                <span class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -start-3 ring-8 ring-white dark:ring-gray-900 dark:bg-blue-900">
                    <svg class="w-3 h-3 text-blue-800 dark:text-blue-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/>
                    </svg>
                </span>
                <div class="text-sm font-normal text-gray-500 dark:text-gray-400">
                    {{ $history->created_at->translatedFormat('j F Y, h:i A') }}
                </div>
                <div class="mt-1 flex items-center gap-2 text-sm">
                    <span class="font-semibold text-gray-900 dark:text-white">
                        {{ $history->changedBy->name ?? __('System') }}
                    </span>
                    <span class="text-gray-500">{{ __('changed status to') }}</span>
                    <span class="px-2 py-0.5 font-medium rounded-md bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                        {{ __($history->new_status->value) }}
                    </span>
                </div>
                @if($history->reason)
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-100 dark:border-gray-700">
                        <span class="font-medium">{{ __('Reason') }}:</span> {{ $history->reason }}
                    </div>
                @endif
            </div>
        @empty
            <div class="text-sm text-gray-500">{{ __('No data yet.') }}</div>
        @endforelse
    </div>
</div>