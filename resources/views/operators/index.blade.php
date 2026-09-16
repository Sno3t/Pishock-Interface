<x-simple-layout title="Operator Links">
    @include('layouts.navigationBar')

    <div class="py-12">
        <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Operator links</h1>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Each link below lets one named person operate your devices, within your configured max values,
                without needing an account. Revoke a link at any time to cut off access.
            </p>

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-300">
                    {{ session('status') }}
                </div>
            @endif

            <div class="space-y-6 bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <form action="{{ route('operators.store') }}" method="POST" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="name" value="Operator name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <x-primary-button type="submit">Create link</x-primary-button>
                </form>
            </div>

            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Link</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($operatorTokens as $operatorToken)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $operatorToken->name }}</td>
                            <td class="max-w-xs px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                @if ($operatorToken->isRevoked())
                                    <span class="text-gray-400">&mdash;</span>
                                @else
                                    <span class="break-all">{{ route('pishock.operate', $operatorToken->token) }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($operatorToken->isRevoked())
                                    <span class="text-red-600 dark:text-red-400">Revoked</span>
                                @else
                                    <span class="text-green-600 dark:text-green-400">Active</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @unless ($operatorToken->isRevoked())
                                    <form action="{{ route('operators.destroy', $operatorToken) }}" method="POST" onsubmit="return confirm('Revoke this operator link?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-red-600 hover:text-red-500">Revoke</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">No operator links yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-simple-layout>
