<x-simple-layout title="Add Device">
    @include('layouts.navigationBar')

    <div class="py-12">
        <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
            <div class="space-y-6 bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Add new shocker</h1>

                <form action="{{ route('devices.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="device_name" value="Device name" />
                        <x-text-input id="device_name" name="device_name" type="text" class="mt-1 block w-full" :value="old('device_name')" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('device_name')" />
                    </div>

                    <div>
                        <x-input-label for="share_code" value="Share code" />
                        <x-text-input id="share_code" name="share_code" type="text" class="mt-1 block w-full" :value="old('share_code')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('share_code')" />
                    </div>

                    <x-primary-button type="submit">Add Device</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-simple-layout>
