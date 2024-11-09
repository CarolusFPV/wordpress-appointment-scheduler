<div id="appointment-form-container" style="display:none;">
    <style type="text/css">
        <?php echo esc_html(get_option('event_scheduler_css', '')); ?>
    </style>

    <form class="custom-scheduler-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
        <input type="hidden" name="action" value="submit_appointment">
        
        <input type="text" id="user_name" name="user_name" placeholder="Naam (verplicht)" required>
        <input type="text" id="city" name="city" placeholder="Stad (optioneel)">
        <select id="country" name="country">
            <option value="Nederland" selected>Nederland</option>
            <!-- Add other countries here -->
        </select>
        <input type="email" id="email" name="email" placeholder="Email (verplicht)" required>
        <input type="hidden" id="unix_timestamp" name="unix_timestamp"> <!-- Hidden field for timestamp -->
        <input type="text" id="appointment_datetime" name="appointment_datetime" placeholder="Date and Time" readonly required>

        <input type="hidden" id="page_url" name="page_url" value="<?php echo esc_url('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
        <input type="hidden" id="local_datetime" name="local_datetime">

        <input type="submit" id="submit_appointment" value="Aanmelden">
    </form>
</div>
