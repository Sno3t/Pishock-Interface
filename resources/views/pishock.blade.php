<x-simple-layout title="PiShock Controller">
@if(!empty($devices))
    @if ($operator)
        <div class="mx-auto max-w-xl px-4 py-4 text-sm text-gray-600 dark:text-gray-400 sm:px-6 lg:px-8">
            Controlling as <span class="font-medium text-gray-900 dark:text-gray-100">{{ $operator->name }}</span>
        </div>
    @else
        @include('layouts.navigationBar')
    @endif

    <div class="py-12">
        <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
            <div class="space-y-6 bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200">PiShock Controller</h1>

                @if (session('response'))
                    <div class="rounded-md bg-indigo-50 p-4 text-sm text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                        {{ session('response') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="rounded-md bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
                        {{ session('error') }}
                    </div>
                @endif

                <form id="pishock-form" method="POST"
                      action="{{ $operator ? route('pishock.operate.send', $operator->token) : route('pishock') }}"
                      class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label value="Devices" />
                        <div class="mt-2 space-y-2">
                            @foreach ($devices as $deviceCode => $deviceName)
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" name="deviceShareCodes[]" id="device_{{ $deviceCode }}" value="{{ $deviceCode }}"
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900">
                                    {{ $deviceName }}
                                </label>
                            @endforeach
                        </div>
                        <p id="device-error" class="mt-1 text-sm text-red-600 dark:text-red-400" style="display: none;">
                            Please select at least one device.
                        </p>
                    </div>

                    <div>
                        <x-input-label for="operation" value="Operation" />
                        <select id="operation" name="operation" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="shock">Shock</option>
                            <option value="vibrate">Vibrate</option>
                            <option value="beep">Beep</option>
                        </select>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="duration" value="Duration (seconds)" />
                            <span id="durationValue" class="text-sm text-gray-500 dark:text-gray-400">1</span>
                        </div>
                        <input type="range" id="duration" name="duration" min="1" max="100" value="1"
                               class="max-indicator mt-1 h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 accent-indigo-600 dark:bg-gray-700">
                        @unless ($operator)
                            <x-secondary-button type="button" id="editDurationMax" class="mt-2">Edit Max Duration</x-secondary-button>
                        @endunless
                    </div>

                    <div id="intensity-group">
                        <div class="flex items-center justify-between">
                            <x-input-label for="intensity" value="Intensity" />
                            <span id="intensityValue" class="text-sm text-gray-500 dark:text-gray-400">1</span>
                        </div>
                        <input type="range" id="intensity" name="intensity" min="1" max="100" value="1"
                               class="max-indicator mt-1 h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 accent-indigo-600 dark:bg-gray-700">
                        @unless ($operator)
                            <x-secondary-button type="button" id="editIntensityMax" class="mt-2">Edit Max Intensity</x-secondary-button>
                        @endunless
                    </div>

                    <x-primary-button type="submit">Send Command</x-primary-button>
                </form>
            </div>
        </div>
    </div>
@else
    <div class="mx-auto max-w-xl px-4 py-24 text-center sm:px-6 lg:px-8">
        @if ($operator)
            <p class="text-lg text-gray-700 dark:text-gray-300">
                Oops! The owner of this PiShock controller hasn't set up any devices yet.
            </p>
        @else
            <p class="text-lg text-gray-700 dark:text-gray-300">
                No devices have been set up yet. Go to the
                <a href="{{ route('devices.index') }}" class="text-indigo-600 hover:text-indigo-500">device manager</a>
                to add one.
            </p>
        @endif
    </div>
@endif

<style>
    input[type="range"].max-indicator {
        background-image: linear-gradient(to right, transparent calc(100% - var(--gray-percentage, 0%)), #9ca3af 0%);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('pishock-form');
        if (!form) {
            return;
        }

        const operationSelect = document.getElementById('operation');
        const intensityGroup = document.getElementById('intensity-group');
        const checkboxes = document.querySelectorAll('input[name="deviceShareCodes[]"]');
        const durationInput = document.getElementById('duration');
        const durationValue = document.getElementById('durationValue');
        const intensityInput = document.getElementById('intensity');
        const intensityValue = document.getElementById('intensityValue');
        const deviceError = document.getElementById('device-error');

        const maxValues = @json($maxValues);

        // Restore all the values from the previous submitted page
        checkboxes.forEach(checkbox => {
            if (localStorage.getItem(checkbox.id) === 'true') {
                checkbox.checked = true;
            }
        });
        if (localStorage.getItem('operation')) {
            operationSelect.value = localStorage.getItem('operation');
            toggleIntensityGroup();
            setSliderMaxValues();
        }
        if (localStorage.getItem('duration')) {
            durationInput.value = localStorage.getItem('duration');
            durationValue.textContent = durationInput.value;
        }
        if (localStorage.getItem('intensity')) {
            intensityInput.value = localStorage.getItem('intensity');
            intensityValue.textContent = intensityInput.value;
        }

        operationSelect.addEventListener('change', () => {
            toggleIntensityGroup();
            setSliderMaxValues();
        });

        durationInput.addEventListener('input', () => {
            const maxDuration = getMaxDuration();
            if (parseInt(durationInput.value, 10) > maxDuration) {
                durationInput.value = maxDuration;
            }
            durationValue.textContent = durationInput.value;
        });

        intensityInput.addEventListener('input', () => {
            const maxIntensity = getMaxIntensity();
            if (parseInt(intensityInput.value, 10) > maxIntensity) {
                intensityInput.value = maxIntensity;
            }
            intensityValue.textContent = intensityInput.value;
        });

        // Check if at least one check box is checked and store values
        form.addEventListener('submit', (event) => {
            let isChecked = false;
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    isChecked = true;
                    localStorage.setItem(checkbox.id, checkbox.checked);
                } else {
                    localStorage.removeItem(checkbox.id);
                }
            });

            if (!isChecked) {
                event.preventDefault();
                deviceError.style.display = 'block';
            } else {
                deviceError.style.display = 'none';
            }

            localStorage.setItem('operation', operationSelect.value);
            localStorage.setItem('duration', durationInput.value);
            localStorage.setItem('intensity', intensityInput.value);
        });

        // Function to show/hide intensity group
        function toggleIntensityGroup() {
            if (operationSelect.value === 'shock' || operationSelect.value === 'vibrate') {
                intensityGroup.style.display = 'block';
            } else {
                intensityGroup.style.display = 'none';
            }
        }

        // Enforcing the max value on sliders
        function setSliderMaxValues() {
            const maxDuration = getMaxDuration();
            const maxIntensity = getMaxIntensity();

            setSliderBackground(durationInput, maxDuration);
            setSliderBackground(intensityInput, maxIntensity);

            if (parseInt(durationInput.value, 10) > maxDuration) {
                durationInput.value = maxDuration;
                durationValue.textContent = maxDuration;
            }

            if (parseInt(intensityInput.value, 10) > maxIntensity) {
                intensityInput.value = maxIntensity;
                intensityValue.textContent = maxIntensity;
            }
        }

        // Dynamic slider background
        function setSliderBackground(slider, maxValue) {
            const percentage = 100 - (maxValue / 100 * 100);
            slider.style.setProperty('--gray-percentage', `${percentage}%`);
        }

        function getMaxDuration() {
            const operation = operationSelect.value;
            return maxValues[operation]?.duration || 10;
        }

        function getMaxIntensity() {
            const operation = operationSelect.value;
            return maxValues[operation]?.intensity || 10;
        }

        setSliderMaxValues();

        // Poll for max-value changes made elsewhere (e.g. the owner editing a
        // limit in another tab) so this page reflects them without a reload.
        const MAX_VALUES_POLL_MS = 5000;
        setInterval(async () => {
            try {
                const response = await fetch('{{ route('maxValues') }}', {
                    headers: { 'Accept': 'application/json' },
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                Object.keys(maxValues).forEach(key => delete maxValues[key]);
                Object.assign(maxValues, data);
                setSliderMaxValues();
            } catch (e) {
                // Transient network errors are fine to ignore; we'll try again on the next tick.
            }
        }, MAX_VALUES_POLL_MS);

        @unless ($operator)
        // Persist an edited max value for the current operation to the server
        async function updateMaxValue(type, newValue) {
            if (isNaN(newValue) || newValue < 1 || newValue > 100) {
                alert('Please enter a number between 1 and 100.');
                return;
            }

            const token = form.querySelector('input[name="_token"]').value;

            try {
                const response = await fetch('{{ route('updateMaxValues') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        operation: operationSelect.value,
                        type,
                        max_value: newValue,
                    }),
                });

                if (!response.ok) {
                    alert('Failed to save the new max value.');
                    return;
                }

                const data = await response.json();
                maxValues[data.operation] = maxValues[data.operation] || {};
                maxValues[data.operation][data.type] = data.maxValue;
                setSliderMaxValues();
            } catch (e) {
                alert('Failed to save the new max value.');
            }
        }

        // Event listeners for edit max buttons
        document.getElementById('editDurationMax').addEventListener('click', () => {
            const newMaxDuration = prompt('Enter new max duration:');
            if (newMaxDuration !== null) {
                updateMaxValue('duration', parseInt(newMaxDuration, 10));
            }
        });

        document.getElementById('editIntensityMax').addEventListener('click', () => {
            const newMaxIntensity = prompt('Enter new max intensity:');
            if (newMaxIntensity !== null) {
                updateMaxValue('intensity', parseInt(newMaxIntensity, 10));
            }
        });
        @endunless
    });
</script>
</x-simple-layout>
