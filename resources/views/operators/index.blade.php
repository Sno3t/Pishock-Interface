<x-simple-layout title="Operator Links">
    @include('layouts.navigationBar')

    <div class="py-12">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
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
                <form id="create-operator-form" action="{{ route('operators.store') }}" method="POST" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="name" value="Operator name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="expires_at_local" value="Expires (optional)" />
                        <x-text-input id="expires_at_local" type="datetime-local" class="mt-1 block w-full" />
                        <input type="hidden" name="expires_at" id="expires_at_utc" value="{{ old('expires_at') }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('expires_at')" />
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
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($operatorTokens as $operatorToken)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $operatorToken->name }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                @if ($operatorToken->isRevoked() || $operatorToken->isExpired())
                                    <span class="text-gray-400">&mdash;</span>
                                @else
                                    <button
                                        type="button"
                                        class="copy-link inline-flex items-center gap-1.5 font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        data-copy-text="{{ route('pishock.operate', $operatorToken->token) }}"
                                        title="{{ route('pishock.operate', $operatorToken->token) }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <span class="copy-link-label">Copy link</span>
                                    </button>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $operatorToken->expires_at?->format('Y-m-d H:i \U\T\C') ?? '---' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($operatorToken->isRevoked())
                                    <span class="text-red-600 dark:text-red-400">Revoked</span>
                                @elseif ($operatorToken->isExpired())
                                    <span class="text-red-600 dark:text-red-400">Expired</span>
                                @else
                                    <span class="text-green-600 dark:text-green-400">Active</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @unless ($operatorToken->isRevoked() || $operatorToken->isExpired())
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
                            <td colspan="5" class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">No operator links yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('.copy-link');
            if (!button) {
                return;
            }

            navigator.clipboard.writeText(button.dataset.copyText).then(() => {
                const label = button.querySelector('.copy-link-label');
                const original = label.textContent;
                label.textContent = 'Copied!';
                setTimeout(() => {
                    label.textContent = original;
                }, 1200);
            });
        });

        // datetime-local inputs carry no timezone info, so a naive string
        // like "2026-09-17T11:01" must be read as the browser's local time
        // and converted to a real UTC instant before it reaches the server,
        // otherwise "3 minutes from now" can silently land hours off.
        (() => {
            const localInput = document.getElementById('expires_at_local');
            const utcInput = document.getElementById('expires_at_utc');
            const form = document.getElementById('create-operator-form');

            if (!localInput || !utcInput || !form) {
                return;
            }

            const toLocalInputValue = (date) => {
                const pad = (n) => String(n).padStart(2, '0');
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
            };

            localInput.min = toLocalInputValue(new Date());

            if (utcInput.value) {
                localInput.value = toLocalInputValue(new Date(utcInput.value));
            }

            form.addEventListener('submit', () => {
                utcInput.value = localInput.value ? new Date(localInput.value).toISOString() : '';
            });
        })();
    </script>
</x-simple-layout>
