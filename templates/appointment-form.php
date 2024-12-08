<div id="appointment-form-container" style="display:none;">
    <form class="custom-scheduler-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
        <input type="hidden" name="action" value="submit_appointment">

        <input type="text" id="user_name" name="user_name" placeholder="Naam (verplicht)" required>
        <input type="text" id="city" name="city" placeholder="Stad (optioneel)">
        <select id="country" name="country">
        <optgroup label="Europe">
                <option value="NL">Netherlands</option>
                <option value="GB">United Kingdom</option>
                <option value="AL">Albania</option>
                <option value="AD">Andorra</option>
                <option value="AT">Austria</option>
                <option value="BY">Belarus</option>
                <option value="BE">Belgium</option>
                <option value="BA">Bosnia and Herzegovina</option>
                <option value="BG">Bulgaria</option>
                <option value="HR">Croatia (Hrvatska)</option>
                <option value="CY">Cyprus</option>
                <option value="CZ">Czech Republic</option>
                <option value="FR">France</option>
                <option value="GI">Gibraltar</option>
                <option value="DE">Germany</option>
                <option value="GR">Greece</option>
                <option value="VA">Vatican City</option>
                <option value="HU">Hungary</option>
                <option value="IT">Italy</option>
                <option value="LI">Liechtenstein</option>
                <option value="LU">Luxembourg</option>
                <option value="MK">Macedonia</option>
                <option value="MT">Malta</option>
                <option value="MD">Moldova</option>
                <option value="MC">Monaco</option>
                <option value="ME">Montenegro</option>
                <option value="NL">Netherlands</option>
                <option value="PL">Poland</option>
                <option value="PT">Portugal</option>
                <option value="RO">Romania</option>
                <option value="SM">San Marino</option>
                <option value="RS">Serbia</option>
                <option value="SK">Slovakia</option>
                <option value="SI">Slovenia</option>
                <option value="ES">Spain</option>
                <option value="UA">Ukraine</option>
                <option value="DK">Denmark</option>
                <option value="EE">Estonia</option>
                <option value="FO">Faroe Islands</option>
                <option value="FI">Finland</option>
                <option value="GL">Greenland</option>
                <option value="IS">Iceland</option>
                <option value="IE">Ireland</option>
                <option value="LV">Latvia</option>
                <option value="LT">Lithuania</option>
                <option value="NO">Norway</option>
                <option value="SJ">Svalbard and Jan Mayen Islands</option>
                <option value="SE">Sweden</option>
                <option value="CH">Switzerland</option>
                <option value="TR">Turkey</option>
            </optgroup>
            <optgroup label="North America">
                <option value="US">United States</option>
                <option value="UM">United States Minor Outlying Islands</option>
                <option value="CA">Canada</option>
                <option value="MX">Mexico</option>
                <option value="AI">Anguilla</option>
                <option value="AG">Antigua and Barbuda</option>
                <option value="AW">Aruba</option>
                <option value="BS">Bahamas</option>
                <option value="BB">Barbados</option>
                <option value="BZ">Belize</option>
                <option value="BM">Bermuda</option>
                <option value="VG">British Virgin Islands</option>
                <option value="KY">Cayman Islands</option>
                <option value="CR">Costa Rica</option>
                <option value="CU">Cuba</option>
                <option value="DM">Dominica</option>
                <option value="DO">Dominican Republic</option>
                <option value="SV">El Salvador</option>
                <option value="GD">Grenada</option>
                <option value="GP">Guadeloupe</option>
                <option value="GT">Guatemala</option>
                <option value="HT">Haiti</option>
                <option value="HN">Honduras</option>
                <option value="JM">Jamaica</option>
                <option value="MQ">Martinique</option>
                <option value="MS">Montserrat</option>
                <option value="AN">Netherlands Antilles</option>
                <option value="NI">Nicaragua</option>
                <option value="PA">Panama</option>
                <option value="PR">Puerto Rico</option>
                <option value="KN">Saint Kitts and Nevis</option>
                <option value="LC">Saint Lucia</option>
                <option value="VC">Saint Vincent and the Grenadines</option>
                <option value="TT">Trinidad and Tobago</option>
                <option value="TC">Turks and Caicos Islands</option>
                <option value="VI">US Virgin Islands</option>
            </optgroup>
            <optgroup label="South America">
                <option value="AR">Argentina</option>
                <option value="BO">Bolivia</option>
                <option value="BR">Brazil</option>
                <option value="CL">Chile</option>
                <option value="CO">Colombia</option>
                <option value="EC">Ecuador</option>
                <option value="FK">Falkland Islands (Malvinas)</option>
                <option value="GF">French Guiana</option>
                <option value="GY">Guyana</option>
                <option value="PY">Paraguay</option>
                <option value="PE">Peru</option>
                <option value="SR">Suriname</option>
                <option value="UY">Uruguay</option>
                <option value="VE">Venezuela</option>
            </optgroup>
            <optgroup label="Asia">
                <option value="AF">Afghanistan</option>
                <option value="AM">Armenia</option>
                <option value="AZ">Azerbaijan</option>
                <option value="BH">Bahrain</option>
                <option value="BD">Bangladesh</option>
                <option value="BT">Bhutan</option>
                <option value="IO">British Indian Ocean Territory</option>
                <option value="BN">Brunei Darussalam</option>
                <option value="KH">Cambodia</option>
                <option value="CN">China</option>
                <option value="CX">Christmas Island</option>
                <option value="CC">Cocos (Keeling) Islands</option>
                <option value="GE">Georgia</option>
                <option value="HK">Hong Kong</option>
                <option value="IN">India</option>
                <option value="ID">Indonesia</option>
                <option value="IR">Iran</option>
                <option value="IQ">Iraq</option>
                <option value="IL">Israel</option>
                <option value="JP">Japan</option>
                <option value="JO">Jordan</option>
                <option value="KZ">Kazakhstan</option>
                <option value="KW">Kuwait</option>
                <option value="KG">Kyrgyzstan</option>
                <option value="LA">Lao</option>
                <option value="LB">Lebanon</option>
                <option value="MY">Malaysia</option>
                <option value="MV">Maldives</option>
                <option value="MN">Mongolia</option>
                <option value="MM">Myanmar (Burma)</option>
                <option value="NP">Nepal</option>
                <option value="OM">Oman</option>
                <option value="PK">Pakistan</option>
                <option value="PH">Philippines</option>
                <option value="QA">Qatar</option>
                <option value="RU">Russian Federation</option>
                <option value="SA">Saudi Arabia</option>
                <option value="SG">Singapore</option>
                <option value="SK">South Korea</option>
                <option value="LK">Sri Lanka</option>
                <option value="SY">Syria</option>
                <option value="TW">Taiwan</option>
                <option value="TJ">Tajikistan</option>
                <option value="TH">Thailand</option>
                <option value="TP">East Timor</option>
                <option value="TM">Turkmenistan</option>
                <option value="AE">United Arab Emirates</option>
                <option value="UZ">Uzbekistan</option>
                <option value="VN">Vietnam</option>
                <option value="YE">Yemen</option>
            </optgroup>
            <optgroup label="Australia / Oceania">
                <option value="AS">American Samoa</option>
                <option value="AU">Australia</option>
                <option value="CK">Cook Islands</option>
                <option value="FJ">Fiji</option>
                <option value="PF">French Polynesia (Tahiti)</option>
                <option value="GU">Guam</option>
                <option value="KB">Kiribati</option>
                <option value="MH">Marshall Islands</option>
                <option value="FM">Micronesia, Federated States of</option>
                <option value="NR">Nauru</option>
                <option value="NC">New Caledonia</option>
                <option value="NZ">New Zealand</option>
                <option value="NU">Niue</option>
                <option value="MP">Northern Mariana Islands</option>
                <option value="PW">Palau</option>
                <option value="PG">Papua New Guinea</option>
                <option value="PN">Pitcairn</option>
                <option value="WS">Samoa</option>
                <option value="SB">Solomon Islands</option>
                <option value="TK">Tokelau</option>
                <option value="TO">Tonga</option>
                <option value="TV">Tuvalu</option>
                <option value="VU">Vanuatu</option>
                <option valud="WF">Wallis and Futuna Islands</option>
            </optgroup>
            <optgroup label="Africa">
                <option value="DZ">Algeria</option>
                <option value="AO">Angola</option>
                <option value="BJ">Benin</option>
                <option value="BW">Botswana</option>
                <option value="BF">Burkina Faso</option>
                <option value="BI">Burundi</option>
                <option value="CM">Cameroon</option>
                <option value="CV">Cape Verde</option>
                <option value="CF">Central African Republic</option>
                <option value="TD">Chad</option>
                <option value="KM">Comoros</option>
                <option value="CG">Congo</option>
                <option value="CD">Congo, the Democratic Republic of the</option>
                <option value="DJ">Dijibouti</option>
                <option value="EG">Egypt</option>
                <option value="GQ">Equatorial Guinea</option>
                <option value="ER">Eritrea</option>
                <option value="ET">Ethiopia</option>
                <option value="GA">Gabon</option>
                <option value="GM">Gambia</option>
                <option value="GH">Ghana</option>
                <option value="GN">Guinea</option>
                <option value="GW">Guinea-Bissau</option>
                <option value="CI">Cote d'Ivoire (Ivory Coast)</option>
                <option value="KE">Kenya</option>
                <option value="LS">Lesotho</option>
                <option value="LR">Liberia</option>
                <option value="LY">Libya</option>
                <option value="MG">Madagascar</option>
                <option value="MW">Malawi</option>
                <option value="ML">Mali</option>
                <option value="MR">Mauritania</option>
                <option value="MU">Mauritius</option>
                <option value="YT">Mayotte</option>
                <option value="MA">Morocco</option>
                <option value="MZ">Mozambique</option>
                <option value="NA">Namibia</option>
                <option value="NE">Niger</option>
                <option value="NG">Nigeria</option>
                <option value="RE">Reunion</option>
                <option value="RW">Rwanda</option>
                <option value="ST">Sao Tome and Principe</option>
                <option value="SH">Saint Helena</option>
                <option value="SN">Senegal</option>
                <option value="SC">Seychelles</option>
                <option value="SL">Sierra Leone</option>
                <option value="SO">Somalia</option>
                <option value="ZA">South Africa</option>
                <option value="SS">South Sudan</option>
                <option value="SD">Sudan</option>
                <option value="SZ">Swaziland</option>
                <option value="TZ">Tanzania</option>
                <option value="TG">Togo</option>
                <option value="TN">Tunisia</option>
                <option value="UG">Uganda</option>
                <option value="EH">Western Sahara</option>
                <option value="ZM">Zambia</option>
                <option value="ZW">Zimbabwe</option>
            </optgroup>
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
