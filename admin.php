<?php
// Add submenu pages for the Scheduler
add_action('admin_menu', function() {
    // Submenu for viewing and managing the Schedule
    add_menu_page(
        'Appointment Schedule', 
        'Schedule', 
        'manage_options', 
        'custom-scheduler-schedule', 
        'custom_scheduler_schedule_page', 
        'dashicons-calendar-alt', 
        6 
    );

    // Submenu for Scheduler Settings
    add_submenu_page(
        'custom-scheduler-schedule', 
        'Scheduler Settings', 
        'Settings', 
        'manage_options', 
        'custom-scheduler-settings', 
        'custom_scheduler_settings_page' 
    );

    // Submenu for manually adding an appointment
    add_submenu_page(
        'custom-scheduler-schedule', 
        'Add Appointment', 
        'Add Appointment', 
        'manage_options', 
        'custom-scheduler-add-appointment', 
        'custom_scheduler_add_appointment_page'
    );
});

// Scheduler Settings Page
function custom_scheduler_settings_page() {
    // Handle saving the settings
    if (isset($_POST['save_scheduler_settings'])) {
        update_option('event_scheduler_css', sanitize_textarea_field($_POST['event_scheduler_css']));

        echo '<div class="updated"><p>Settings saved successfully.</p></div>';
    }

    // Retrieve current settings
    $scheduler_css = get_option('event_scheduler_css', '');

    ?>
    <div class="wrap">
        <h2>Scheduler Settings</h2>
        <form method="post" action="">
            <h3>Custom CSS for Shortcode</h3>
            <textarea name="event_scheduler_css" rows="10" cols="50"><?php echo esc_textarea($scheduler_css); ?></textarea>
            <p><input type="submit" name="save_scheduler_settings" value="Save Settings" class="button-primary"></p>
        </form>
    </div>
    <?php
}

// Manually add appointments page
function custom_scheduler_add_appointment_page() {
    global $wpdb;

    // Handle form submission
    if (isset($_POST['add_appointment'])) {
        $user_name = sanitize_text_field($_POST['user_name']);
        $city = sanitize_text_field($_POST['city']);
        $country = sanitize_text_field($_POST['country']);
        $appointment_datetime = strtotime(sanitize_text_field($_POST['appointment_datetime']));

        // Insert appointment into database
        $wpdb->insert(EVENT_SCHEDULER_TABLE, [
            'user_name' => $user_name,
            'city' => $city,
            'country' => $country,
            'appointment_datetime' => $appointment_datetime
        ]);

        echo '<div class="updated"><p>Appointment added successfully.</p></div>';
    }

    ?>
    <div class="wrap">
        <h2>Add Appointment</h2>
        <form method="post">
            <label for="user_name">User Name:</label>
            <input type="text" name="user_name" required>
            <label for="city">City:</label>
            <input type="text" name="city">
            <label for="country">Country:</label>
            <input type="text" name="country">
            <label for="appointment_datetime">Date and Time:</label>
            <input type="datetime-local" name="appointment_datetime" required>
            <input type="submit" name="add_appointment" value="Add Appointment" class="button-primary">
        </form>
    </div>
    <?php
}

// Schedule List Page
function custom_scheduler_schedule_page() {
    global $wpdb;

    // Handle deletion of an appointment
    if (isset($_GET['delete_appointment']) && isset($_GET['appointment_id'])) {
        $appointment_id = intval($_GET['appointment_id']);
        $wpdb->delete(EVENT_SCHEDULER_TABLE, ['id' => $appointment_id]);
        echo '<div class="updated"><p>Appointment deleted successfully.</p></div>';
    }

    // Fetch all confirmed appointments
    $appointments = $wpdb->get_results("SELECT * FROM " . EVENT_SCHEDULER_TABLE . " ORDER BY appointment_datetime", OBJECT);
    ?>
    <div class="wrap">
        <h2>Confirmed Appointments</h2>
        <form method="post">
            <input type="text" name="search_appointment" placeholder="Search appointments...">
            <input type="submit" value="Search" class="button">
        </form>
        <?php if (empty($appointments)): ?>
            <p>No appointments found.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>City</th>
                        <th>Country</th>
                        <th>Appointment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><?php echo esc_html($appointment->user_name); ?></td>
                            <td><?php echo esc_html($appointment->city); ?></td>
                            <td><?php echo esc_html($appointment->country); ?></td>
                            <td><?php echo date('Y-m-d H:i', $appointment->appointment_datetime); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['delete_appointment' => 1, 'appointment_id' => $appointment->id])); ?>" class="button">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
?>
