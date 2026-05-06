<div class="mt-8 bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
    <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
        <flux:icon.clock class="w-5 h-5 text-gray-400" />
        {{ __('Status History') }}
    </h3>
    
    <div class="flow-root relative">
        <ul role="list" class="-mb-8">
            @forelse($histories as $history)
                <li>
                    <div class="relative pb-8">
                        @if(!$loop->last)
                            <span class="absolute top-5 left-5 rtl:right-5 rtl:left-auto -ml-[1px] rtl:-mr-[1px] rtl:ml-0 h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                        @endif
                        <div class="relative flex items-start space-x-3 rtl:space-x-reverse">
                            <div class="relative">
                                @if($loop->first)
                                    <span class="h-10 w-10 rounded-full bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                        <flux:icon.check-circle class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                    </span>
                                @else
                                    <span class="h-10 w-10 rounded-full bg-gray-50 dark:bg-gray-800 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                        <flux:icon.arrow-path class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                    </span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1 py-1.5">
                                <div class="text-sm text-gray-500 dark:text-gray-400 flex justify-between items-center">
                                    <div>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $history->changedBy->name ?? __('System') }}
                                        </span>
                                        <span class="mx-1">{{ __('changed status to') }}</span>
                                        <span class="inline-flex items-center rounded-md bg-gray-50 dark:bg-gray-800 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 ring-1 ring-inset ring-gray-500/10 dark:ring-gray-400/20">
                                            {{ __($history->new_status->value ?? $history->new_status) }}
                                        </span>
                                    </div>
                                    <div class="whitespace-nowrap text-right text-xs text-gray-500 dark:text-gray-400 flex flex-col items-end">
                                        <time datetime="{{ $history->created_at->toIso8601String() }}">
                                            {{ $history->created_at->diffForHumans() }}
                                        </time>
                                        <span class="text-[10px] text-gray-400 mt-0.5">{{ $history->created_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                </div>
                                @if($history->reason)
                                    <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg border-l-2 border-primary-500 rtl:border-l-0 rtl:border-r-2 rtl:border-primary-500 ring-1 ring-inset ring-gray-900/5 dark:ring-white/5">
                                        <p class="flex items-start gap-2">
                                            <flux:icon.chat-bubble-bottom-center-text class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
                                            <span>{{ $history->reason }}</span>
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </li>
            @empty
                <div class="text-center py-6">
                    <flux:icon.inbox class="mx-auto h-8 w-8 text-gray-400" />
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ __('No data yet.') }}</h3>
                </div>
            @endforelse
        </ul>
    </div>
</div>