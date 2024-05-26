<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PiShock Controller</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
        .right-stripe-duration {
            background: linear-gradient(to right, transparent calc(100% - 50%), grey 0%);
        }
        .right-stripe-intensity {
            background: linear-gradient(to right, transparent calc(100% - 50%), grey 0%);
        }
    </style>

</head>
<body>
<div class="container mt-5">
    <h1>PiShock Controller</h1>
    @if (session('response'))
        <div class="alert alert-info">{{ session('response') }}</div>
    @endif
    <form id="pishock-form" method="POST" action="{{ route('pishock') }}">
        @csrf
        <div class="mb-3">
            <a href="{{ route('devices.index') }}">Device management</a> <br>
            <label for="deviceShareCodes">Devices:</label><br>
            @foreach ($devices as $deviceCode => $deviceName)
                <input type="checkbox" name="deviceShareCodes[]" id="device_{{ $deviceCode }}"
                       value="{{ $deviceCode }}">
                <label for="device_{{ $deviceCode }}">{{ $deviceName }}</label> <br>
            @endforeach
            <div id="device-error" class="text-danger" style="display: none;">Please select at least one device.</div>
        </div>
        <div class="mb-3">
            <label for="operation" class="form-label">Operation</label>
            <select class="form-select" id="operation" name="operation" required>
                <option value="">Select Operation</option>
                <option value="shock">Shock</option>
                <option value="vibrate">Vibrate</option>
                <option value="beep">Beep</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="duration" class="form-label">Duration (seconds): <span id="durationValue">1</span></label>
            <input type="range" class="form-range right-stripe-duration" id="duration" name="duration" min="1" max="100" value="1">
        </div>
        <div class="mb-3" id="intensity-group">
            <label for="intensity" class="form-label">Intensity: <span id="intensityValue">1</span></label>
            <input type="range" class="form-range right-stripe-intensity" id="intensity" name="intensity" min="1" max="100" value="1">
        </div>
        <button type="submit" class="btn btn-primary">Send Command</button>
    </form>
</div>

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

        const predefinedMaxDuration = 50;
        const predefinedMaxIntensity = 75;

        // Restore checkbox states
        checkboxes.forEach(checkbox => {
            if (localStorage.getItem(checkbox.id) === 'true') {
                checkbox.checked = true;
            }
        });

        // Restore select value
        if (localStorage.getItem('operation')) {
            operationSelect.value = localStorage.getItem('operation');
            toggleIntensityGroup();
        }

        // Restore slider values and set displayed values
        if (localStorage.getItem('duration')) {
            durationInput.value = localStorage.getItem('duration');
            durationValue.textContent = durationInput.value;
        }

        if (localStorage.getItem('intensity')) {
            intensityInput.value = localStorage.getItem('intensity');
            intensityValue.textContent = intensityInput.value;
        }

        operationSelect.addEventListener('change', toggleIntensityGroup);

        durationInput.addEventListener('input', () => {
            if (parseInt(durationInput.value, 10) > predefinedMaxDuration) {
                durationInput.value = predefinedMaxDuration;
            }
            durationValue.textContent = durationInput.value;
        });

        intensityInput.addEventListener('input', () => {
            if (parseInt(intensityInput.value, 10) > predefinedMaxIntensity) {
                intensityInput.value = predefinedMaxIntensity;
            }
            intensityValue.textContent = intensityInput.value;
        });

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

        function toggleIntensityGroup() {
            if (operationSelect.value === 'shock' || operationSelect.value === 'vibrate') {
                intensityGroup.style.display = 'block';
            } else {
                intensityGroup.style.display = 'none';
            }
        }
    });
</script>
</body>
</html>
