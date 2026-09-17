<x-simple-layout title="Operation History">
    @include('layouts.navigationBar')

    <div class="py-12">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Operation history</h1>

                <form action="{{ route('history.prune') }}" method="POST" onsubmit="return confirm('Archive history entries older than the retention period? They stay in the database but disappear from this list.')">
                    @csrf
                    <x-secondary-button type="submit" class="whitespace-nowrap">Archive old history</x-secondary-button>
                </form>
            </div>

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-300">
                    {{ session('status') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">When</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Who</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Devices</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Operation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Intensity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($history as $command)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $command['created_at']->format('Y-m-d H:i:s') }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $command['who'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $command['devices'] ? implode(', ', $command['devices']) : '---' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm capitalize text-gray-700 dark:text-gray-300">{{ $command['operation'] }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $command['values']['duration'] ?? '---' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $command['values']['intensity'] ?? '---' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($command['succeeded'])
                                    <span class="text-green-600 dark:text-green-400">Success</span>
                                @else
                                    <span class="text-red-600 dark:text-red-400">Failed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">No commands have been sent yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $history->links() }}
        </div>
    </div>
</x-simple-layout>
