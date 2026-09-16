<x-simple-layout title="Operation History">
    @include('layouts.navigationBar')

    <div class="py-12">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Operation history</h1>

            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">When</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Who</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Operation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Value</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($history as $entry)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $entry->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $entry->user?->name ?? $entry->operatorToken?->name ?? 'Unknown' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm capitalize text-gray-700 dark:text-gray-300">{{ $entry->operation }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm capitalize text-gray-700 dark:text-gray-300">{{ $entry->type }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $entry->value }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">No commands have been sent yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $history->links() }}
        </div>
    </div>
</x-simple-layout>
