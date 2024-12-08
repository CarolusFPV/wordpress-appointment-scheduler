<div id="appointment-form-container" style="display:none;">
    <form class="custom-scheduler-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
        <input type="hidden" name="action" value="submit_appointment">

        <input type="text" id="user_name" name="user_name" placeholder="Naam (verplicht)" required>
        <input type="text" id="city" name="city" placeholder="Stad (optioneel)">
        <select id="country" name="country">
            <optgroup label="Europe">
                <option value="NL">Netherlands</option>
                <option value="GB">United Kingdom</option>
                <!-- Add remaining country options -->
            </optgroup>
            <!-- Add other continent optgroups -->
        </select>

        <input type="email" id="email" name="email" placeholder="Email (verplicht)" required>
        
        <div class="auto-repeat">
            <label for="repeat_toggle">Automatisch Herhalen</label>
            <input type="checkbox" id="repeat_toggle" name="repeat_toggle" onclick="toggleRepeatOptions(this.checked)">
        </div>

        <div id="repeat-options" style="display: none">
            <label for="start_time">Tijd:</label>
            <select id="start_time" name="start_time">
                <?php
                for ($hour = 0; $hour < 24; $hour++) {
                    for ($minute = 0; $minute < 60; $minute += 30) {
                        $time = sprintf('%02d:%02d', $hour, $minute);
                        echo "<option value='$time'>$time</option>";
                    }
                }
                ?>
            </select>

            <label for="start_date">Van:</label>
            <input type="date" id="start_date" name="start_date" min="<?php echo date('Y-m-d'); ?>">

            <label for="end_date">Tot en met:</label>
            <input type="date" id="end_date" name="end_date">

            <label for="repeat_type">Herhaling:</label>
            <select id="repeat_type" name="repeat_type">
                <option value="weekly" selected>Wekelijks</option>
                <option value="daily">Dagelijks</option>
            </select>
        </div>

        
        <input type="text" id="appointment_datetime" name="appointment_datetime" placeholder="Datum en Tijd" readonly required>
        <input type="hidden" id="unix_timestamp" name="unix_timestamp"> <!-- This is what gets sent to the server -->
        <input type="hidden" id="page_url" name="page_url" value="<?php echo esc_url('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
        
        <input type="submit" id="submit_appointment" value="Aanmelden">
    </form>
</div>

<script type="text/javascript">
    function toggleRepeatOptions(show) {
        const repeatOptions = document.getElementById('repeat-options');
        const appointmentDatetime = document.getElementById('appointment_datetime');
        const startDateField = document.getElementById('start_date');
        const endDateField = document.getElementById('end_date');
        const startTimeField = document.getElementById('start_time');
        const unixTimestampField = document.getElementById('unix_timestamp');

        repeatOptions.style.display = show ? 'block' : 'none';
        appointmentDatetime.style.display = show ? 'none' : 'block'; // Hide appointment_datetime when repeat is toggled

        if (show) {
            const unixTimestamp = parseInt(unixTimestampField.value, 10);
            if (!isNaN(unixTimestamp)) {
                const date = new Date(unixTimestamp * 1000); // Convert Unix timestamp to milliseconds

                // Format the start date as YYYY-MM-DD
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                startDateField.value = `${year}-${month}-${day}`;

                // Format the start time as HH:MM
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                startTimeField.value = `${hours}:${minutes}`;

                // Calculate the end date by adding 7 days
                const endDate = new Date(date);
                endDate.setDate(endDate.getDate() + 7);

                // Format the end date as YYYY-MM-DD
                const endYear = endDate.getFullYear();
                const endMonth = String(endDate.getMonth() + 1).padStart(2, '0');
                const endDay = String(endDate.getDate()).padStart(2, '0');
                endDateField.value = `${endYear}-${endMonth}-${endDay}`;

            } else {
                startDateField.value = '';
                startTimeField.value = '00:00'; // Default to midnight
            }
        } else {
            startDateField.value = '';
            startTimeField.value = '00:00';
            endDateField.value = '';
        }
    }

    document.querySelector('.custom-scheduler-form').addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(this);

        const repeatToggle = document.getElementById('repeat_toggle');
        let startDateTimeUTC, startDateTimeLocal;

        if (!repeatToggle.checked) {
            // For non-repeating appointments, use the unix_timestamp field
            const appointmentDateTimeInput = document.getElementById('unix_timestamp').value;

            if (appointmentDateTimeInput) {
                // Parse the Unix timestamp from the value
                const unixTimestamp = parseInt(appointmentDateTimeInput, 10);

                // Convert to UTC and Local Date objects
                startDateTimeUTC = new Date(unixTimestamp * 1000); // Unix timestamp to UTC Date
                startDateTimeLocal = startDateTimeUTC; // Already converted to local time by JavaScript

                // Add to form data
                formData.set('unix_timestamp', unixTimestamp);
                formData.set('local_start_dateTime', startDateTimeLocal.toLocaleString());
            } else {
                alert('Please provide a valid appointment date and time.');
                return;
            }

            // Remove repeat-related fields
            formData.delete('repeat_type');
            formData.delete('start_date');
            formData.delete('start_time');
            formData.delete('appointment_datetime');
            formData.delete('end_date');
        } else {
            // For repeating appointments, use start_date and start_time fields
            const startDateInput = document.getElementById('start_date').value;
            const startTimeInput = document.getElementById('start_time').value;
            const [hours, minutes] = startTimeInput.split(':').map(Number);
            const [year, month, day] = startDateInput.split('-').map(Number);

            // Local start date and time
            const startDateTimeLocal = new Date(year, month - 1, day, hours, minutes, 0);

            // UTC timestamp from local time
            const startUnixTimestamp = Math.floor(startDateTimeLocal.getTime() / 1000);

            // Add start date and time to form data
            formData.set('unix_timestamp', startUnixTimestamp);
            formData.set('local_start_dateTime', startDateTimeLocal.toLocaleString());

            // Handle the end date
            const endDateInput = document.getElementById('end_date').value;
            if (endDateInput) {
                const [endYear, endMonth, endDay] = endDateInput.split('-').map(Number);
                const endDateTimeLocal = new Date(endYear, endMonth - 1, endDay, hours, minutes, 0);

                // UTC timestamp from local time
                const endUnixTimestamp = Math.floor(endDateTimeLocal.getTime() / 1000);

                // Add end date to form data
                formData.set('end_date', endUnixTimestamp);
                formData.set('local_end_dateTime', endDateTimeLocal.toLocaleString());
            } else {
                formData.delete('end_date');
                formData.delete('local_end_dateTime');
            }
        }





        // Submit the form via AJAX
        fetch(scheduler_data.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(formData),
        })
            .then((response) => response.json())
            .then((data) => {
                const formContainer = document.getElementById('appointment-form-container');
                if (data.success) {
                    formContainer.innerHTML = `<div class="appointment-message">${data.data.message.replace(/\n/g, '<br>')}</div>`;
                } else {
                    formContainer.innerHTML = `<div class="appointment-message">${(data.data.message || 'Error scheduling appointment.').replace(/\n/g, '<br>')}</div>`;
                }
            })
            .catch((error) => {
                alert('Error: ' + error.message);
            });
    });
</script>
