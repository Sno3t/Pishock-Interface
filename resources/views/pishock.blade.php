<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PiShock Controller</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
        .right-stripe {
            background: linear-gradient(to right, transparent calc(100% - var(--gray-percentage, 0%)), grey 0%);
        }
    </style>
</head>
<body>

@if(!empty($devices))
    @auth
        @include('layouts.navigationBar')
    @else
        <a href="{{ route('login') }}">Login</a>
    @endauth

    <div class="container mt-5">
        <h1>PiShock Controller</h1>
        @if (session('response'))
            <div class="alert alert-info">{{ session('response') }}</div>
        @endif
        <form id="pishock-form" method="POST" action="{{ route('pishock') }}">
            @csrf
            <div class="mb-3">
                <label for="deviceShareCodes">Devices:</label><br>
                @foreach ($devices as $deviceCode => $deviceName)
                    <input type="checkbox" name="deviceShareCodes[]" id="device_{{ $deviceCode }}"
                           value="{{ $deviceCode }}">
                    <label for="device_{{ $deviceCode }}">{{ $deviceName }}</label> <br>
                @endforeach
                <div id="device-error" class="text-danger" style="display: none;">Please select at least one device.
                </div>
            </div>
            <div class="mb-3">
                <label for="operation" class="form-label">Operation</label>
                <select class="form-select" id="operation" name="operation" required>
                    <option value="shock">Shock</option>
                    <option value="vibrate">Vibrate</option>
                    <option value="beep">Beep</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="duration" class="form-label">Duration (seconds): <span id="durationValue">1</span></label>
                <input type="range" class="form-range right-stripe" id="duration" name="duration" min="1" max="100"
                       value="1">
                @auth
                    <button type="button" class="btn btn-secondary btn-sm" id="editDurationMax">Edit Max Duration
                    </button>
                @endauth
            </div>
            <div class="mb-3" id="intensity-group">
                <label for="intensity" class="form-label">Intensity: <span id="intensityValue">1</span></label>
                <input type="range" class="form-range right-stripe" id="intensity" name="intensity" min="1" max="100"
                       value="1">
                @auth
                    <button type="button" class="btn btn-secondary btn-sm" id="editIntensityMax">Edit Max Intensity
                    </button>
                @endauth
            </div>
            <button type="submit" class="btn btn-primary">Send Command</button>
        </form>
    </div>
@else

    @auth
        <div class="container">
            <div class="h1 align-content-center">No devices have been setup up, please go to the <a
                    href="{{ route('devices.index') }}">device manager</a> to add devices.
            </div>
        </div>
    @else
        <div class="container">
            <div class="h1 align-content-center">Oops! The owner of this Pishock controller has no devices setup!</div>
        </div>
    @endauth


@endif

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('pishock-form');
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

        @auth
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
        @endauth
    });
</script>
</body>
</html>
