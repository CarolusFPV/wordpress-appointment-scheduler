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

    // Submenu for Message Templates
    add_submenu_page(
        'custom-scheduler-schedule', 
        'Message Templates', 
        'Message Templates', 
        'manage_options', 
        'custom-scheduler-message-templates', 
        'custom_scheduler_message_templates_page'
    );
});

// Default templates for each message
function get_default_templates() {
    return [
        'appointment_submit_success' => "Thank you, %name%! Your appointment has been scheduled for %date% at %time% in %city%, %country%.",
        'appointment_submit_failed' => "We're sorry, %name%. Your appointment could not be scheduled for %date% at %time%. Error: %error message%. Please try again.",
        'appointment_scheduled' => "Hello %name%, your appointment for %date% at %time% in %city%, %country% is confirmed!",
        'appointment_email_verification' => "Hi %name%, please verify your appointment scheduled for %date% at %time% in %city%, %country%. Click here to verify: %verify url%",
        'appointment_cancellation' => "Your appointment has been successfully cancelled. If you have any questions, please contact us."
    ];
}

// Message Templates Page
function custom_scheduler_message_templates_page() {
    // Define template options and their placeholders
    $templates = [
        'appointment_submit_success' => [
            'label' => 'Appointment Submit Success Message',
            'placeholders' => '%name%, %time%, %city%, %country%, %email%, %repeat_type%, %end_date%'
        ],
        'appointment_submit_failed' => [
            'label' => 'Appointment Submit Failed Message',
            'placeholders' => '%name%, %time%, %city%, %country%, %email%, %error_message%'
        ],
        'appointment_scheduled' => [
            'label' => 'Appointment Scheduled Message',
            'placeholders' => '%name%, %time%, %city%, %country%, %repeat_type%, %end_date%'
        ],
        'appointment_email_verification' => [
            'label' => 'Appointment Verification Email',
            'placeholders' => '%name%, %time%, %city%, %country%, %email%, %verify_url%, %repeat_type%, %end_date%'
        ],
        'appointment_cancellation' => [
            'label' => 'Appointment Cancellation Message',
            'placeholders' => ''
        ]
    ];

    // Load defaults if options do not exist
    $default_templates = get_default_templates();
    foreach ($templates as $option => $details) {
        if (!get_option($option . '_template')) {
            update_option($option . '_template', $default_templates[$option]);
        }
    }

    // Handle saving each template
    foreach ($templates as $option => $details) {
        if (isset($_POST['save_' . $option])) {
            update_option($option . '_template', wp_kses_post($_POST[$option . '_template'])); // using wp_kses_post for preserving placeholders
            echo '<div class="updated"><p>' . $details['label'] . ' saved successfully.</p></div>';
        }
    }

    ?>
    <div class="wrap">
        <h2>Message Templates</h2>
        <form method="post" action="">
            <?php foreach ($templates as $option => $details): 
                $template_content = get_option($option . '_template', ''); ?>
                <h3><?php echo esc_html($details['label']); ?></h3>
                <p><strong>Placeholders:</strong> <?php echo esc_html($details['placeholders']); ?></p>
                <textarea name="<?php echo esc_attr($option); ?>_template" rows="10" cols="80"><?php echo esc_textarea($template_content); ?></textarea>
                <p><input type="submit" name="save_<?php echo esc_attr($option); ?>" value="Save <?php echo esc_html($details['label']); ?>" class="button-primary"></p>
            <?php endforeach; ?>
        </form>
    </div>
    <?php
}

// Scheduler Settings Page
function custom_scheduler_settings_page() {
    // Handle saving the settings
    if (isset($_POST['save_scheduler_settings'])) {
        update_option('event_scheduler_css', wp_unslash($_POST['event_scheduler_css']));
        update_option('event_scheduler_interval', intval($_POST['event_scheduler_interval']));
        echo '<div class="updated"><p>Settings saved successfully.</p></div>';
    }

    // Retrieve current settings
    $scheduler_css = get_option('event_scheduler_css', '');
    $scheduler_interval = get_option('event_scheduler_interval', 60); // Default to 60 minutes

    ?>
    <div class="wrap">
        <h2>Scheduler Settings</h2>
        <form method="post" action="">
            <h3>Custom CSS for Shortcode</h3>
            <textarea name="event_scheduler_css" rows="20" cols="80" style="white-space: pre;"><?php echo esc_textarea($scheduler_css); ?></textarea>
            <h3>Appointment Interval (Minutes)</h3>
            <input type="number" name="event_scheduler_interval" value="<?php echo esc_attr($scheduler_interval); ?>" min="1" />
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

    // Fetch cancellation_token counts for identifying auto-scheduled appointments
    $cancellation_token_counts = $wpdb->get_results("
        SELECT cancellation_token, COUNT(*) as count 
        FROM " . EVENT_SCHEDULER_TABLE . " 
        WHERE cancellation_token IS NOT NULL AND cancellation_token != ''
        GROUP BY cancellation_token
    ", OBJECT);

    // Map cancellation tokens to their counts
    $cancellation_count_map = [];
    foreach ($cancellation_token_counts as $entry) {
        $cancellation_count_map[$entry->cancellation_token] = $entry->count;
    }
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
                        <th>Cancellation ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($appointments as $index => $appointment): 
                        $is_auto_scheduled = isset($cancellation_count_map[$appointment->cancellation_token]) &&
                                            $cancellation_count_map[$appointment->cancellation_token] > 1;

                        // Generate a persistent color for auto-scheduled appointments
                        $background_color = 'transparent'; // Default for single appointments
                        if ($is_auto_scheduled) {
                            // Hash the cancellation token to generate a color
                            $hash = crc32($appointment->cancellation_token); // Convert token to hash
                            $r = ($hash & 0xFF0000) >> 16;
                            $g = ($hash & 0x00FF00) >> 8;
                            $b = ($hash & 0x0000FF);
                            $background_color = "rgba($r, $g, $b, 0.5)"; // Light color with transparency
                        } else {
                            // Alternate row colors for non-auto-scheduled appointments
                            $background_color = $index % 2 === 0 ? '#f9f9f9' : '#ffffff'; // Light and slightly darker
                        }
                    ?>
                        <tr style="background-color: <?php echo $background_color; ?>;">
                            <td><?php echo esc_html($appointment->user_name); ?></td>
                            <td><?php echo esc_html($appointment->city); ?></td>
                            <td><?php echo esc_html($appointment->country); ?></td>
                            <td><?php echo date('Y-m-d H:i', $appointment->appointment_datetime); ?></td>
                            <td><?php echo esc_html($appointment->cancellation_token); ?></td>
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
