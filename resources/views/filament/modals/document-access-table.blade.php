@php
    use App\Modules\Document\Enums\AccessLevel;
@endphp

<div x-data="{ 
    editingAccess: null,
    editAccessLevel: null,
    
    startEdit(accessId, currentLevel) {
        this.editingAccess = accessId;
        this.editAccessLevel = currentLevel;
    },
    
    cancelEdit() {
        this.editingAccess = null;
        this.editAccessLevel = null;
    },
    
    async updateAccess(accessId) {
        const level = this.editAccessLevel;
        
        try {
            const response = await fetch('{{ route('document.access.update', ['access' => '__ID__']) }}'.replace('__ID__', accessId), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ access_level: level })
            });
            
            if (response.ok) {
                window.location.reload();
            }
        } catch (error) {
            console.error('Error updating access:', error);
        }
    },
    
    async deleteAccess(accessId) {
        if (!confirm('Remove this user\'s access to the document?')) {
            return;
        }
        
        try {
            const response = await fetch('{{ route('document.access.destroy', ['access' => '__ID__']) }}'.replace('__ID__', accessId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            });
            
            if (response.ok) {
                window.location.reload();
            }
        } catch (error) {
            console.error('Error deleting access:', error);
        }
    }
}">
    @if($record->sharedAccess->count() > 0)
        <div class="overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            User
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Access Level
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Granted By
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                    @foreach($record->sharedAccess as $access)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $access->user->name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $access->user->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div x-show="editingAccess !== {{ $access->id }}">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($access->access_level === AccessLevel::READ) bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @elseif($access->access_level === AccessLevel::EDIT) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                        @else bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                        @endif">
                                        {{ $access->access_level->label() }}
                                    </span>
                                </div>
                                <div x-show="editingAccess === {{ $access->id }}" style="display: none;">
                                    <select x-model="editAccessLevel" 
                                            class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                        @foreach(AccessLevel::cases() as $level)
                                            <option value="{{ $level->value }}" 
                                                    {{ $access->access_level === $level ? 'selected' : '' }}>
                                                {{ $level->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $access->grantedBy ? $access->grantedBy->name : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <!-- Edit Mode -->
                                    <div x-show="editingAccess !== {{ $access->id }}" class="flex space-x-2">
                                        <button @click="startEdit({{ $access->id }}, '{{ $access->access_level->value }}')"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                title="Edit Access">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button @click="deleteAccess({{ $access->id }})"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="Remove Access">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Save/Cancel Mode -->
                                    <div x-show="editingAccess === {{ $access->id }}" style="display: none;" class="flex space-x-2">
                                        <button @click="updateAccess({{ $access->id }})"
                                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                title="Save">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                        <button @click="cancelEdit()"
                                                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                                title="Cancel">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No shared access</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This document is not shared with anyone.</p>
        </div>
    @endif
</div>
