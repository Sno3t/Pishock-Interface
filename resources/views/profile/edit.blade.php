<x-simple-layout title="Profile">
    @include('layouts.navigationBar')

    <div class="py-12">
        <div class="mx-auto max-w-xl space-y-6 sm:px-6 lg:px-8">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Profile</h1>

            <div class="space-y-6 bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="space-y-6 bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                @include('profile.partials.update-password-form')
            </div>

            <div class="space-y-6 bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-simple-layout>
