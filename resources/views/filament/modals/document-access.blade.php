<div class="space-y-4">
    @if($record->sharedAccess->count() > 0)
        <div class="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-800">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                    Shared With {{ $record->sharedAccess->count() }} {{ Str::plural('User', $record->sharedAccess->count()) }}
                </h3>
                
                <div class="mt-4 space-y-3">
                    @foreach($record->sharedAccess as $access)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $access->user->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $access->user->email }}
                                    </p>
                                    @if($access->grantedBy)
                                        <p class="text-xs text-gray-400 dark:text-gray-500">
                                            Granted by {{ $access->grantedBy->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    @if($access->access_level === \App\Modules\Document\Enums\AccessLevel::READ) bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                    @elseif($access->access_level === \App\Modules\Document\Enums\AccessLevel::EDIT) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @else bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                    @endif">
                                    {{ $access->access_level->label() }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This document is not shared with anyone.</p>
        </div>
    @endif
</div>
