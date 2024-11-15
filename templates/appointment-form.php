<div id="appointment-form-container" style="display:none;">
    <form class="custom-scheduler-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
        <input type="hidden" name="action" value="submit_appointment">
        
        <input type="text" id="user_name" name="user_name" placeholder="Naam (verplicht)" required>
        <input type="text" id="city" name="city" placeholder="Stad (optioneel)">
        <select id="country" name="country">
            <option value="Nederland" selected>Nederland</option>
            <!-- Voeg andere landen toe -->
        </select>
        <input type="email" id="email" name="email" placeholder="Email (verplicht)" required>
        <input type="hidden" id="unix_timestamp" name="unix_timestamp"> <!-- Verborgen veld voor timestamp -->
        <input type="text" id="appointment_datetime" name="appointment_datetime" placeholder="Datum en Tijd" readonly required>

        <input type="hidden" id="page_url" name="page_url" value="<?php echo esc_url('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
        <input type="hidden" id="local_datetime" name="local_datetime">

        <!-- Automatisch Herhalen -->
        <div class="auto-repeat">
            <label for="repeat_toggle">Automatisch Herhalen</label>
            <input type="checkbox" id="repeat_toggle" name="repeat_toggle" onclick="toggleRepeatOptions(this.checked)">
        </div>

        <div id="repeat-options" style="display: none">
            <label for="repeat_type">Herhaling:</label>
            <select id="repeat_type" name="repeat_type">
                <option value="weekly" selected>Wekelijks</option>
                <option value="daily">Dagelijks</option>
            </select>

            <label for="end_date">Einddatum:</label>
            <input type="date" id="end_date" name="end_date" 
                   value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" 
                   min="<?php echo date('Y-m-d'); ?>" 
                   max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
        </div>

        <input type="submit" id="submit_appointment" value="Aanmelden">
    </form>
</div>

<script type="text/javascript">
    function toggleRepeatOptions(show) {
        const repeatOptions = document.getElementById('repeat-options');
        repeatOptions.style.display = show ? 'block' : 'none';
    }
</script>
