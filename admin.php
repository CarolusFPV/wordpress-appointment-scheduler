<?php
// Add submenu pages for the Scheduler
add_action('admin_menu', function() {
    $capability = 'edit_posts'; 

    add_menu_page(
        'Appointment Schedule', 
        'Schedule', 
        $capability, 
        'custom-scheduler-schedule', 
        'custom_scheduler_schedule_page', 
        'dashicons-calendar-alt', 
        6 
    );

    add_submenu_page(
        'custom-scheduler-schedule', 
        'Scheduler Settings', 
        'Settings', 
        $capability, 
        'custom-scheduler-settings', 
        'custom_scheduler_settings_page' 
    );

    add_submenu_page(
        'custom-scheduler-schedule', 
        'Add Appointment', 
        'Add Appointment', 
        $capability, 
        'custom-scheduler-add-appointment', 
        'custom_scheduler_add_appointment_page'
    );

    add_submenu_page(
        'custom-scheduler-schedule', 
        'Message Templates', 
        'Message Templates', 
        $capability, 
        'custom-scheduler-message-templates', 
        'custom_scheduler_message_templates_page'
    );

    add_submenu_page(
        'custom-scheduler-schedule', 
        'Import/Export', 
        'Import/Export', 
        $capability, 
        'custom-scheduler-import-export', 
        'custom_scheduler_import_export_page'
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

    // Handle search
    $search_query = '';
    if (isset($_POST['search_appointment']) && !empty($_POST['search_appointment'])) {
        $search_query = sanitize_text_field($_POST['search_appointment']);
    }

    // Fetch appointments with or without a search query
    $sql = "SELECT * FROM " . EVENT_SCHEDULER_TABLE;
    if (!empty($search_query)) {
        $sql .= $wpdb->prepare(
            " WHERE user_name LIKE %s OR city LIKE %s OR country LIKE %s",
            '%' . $wpdb->esc_like($search_query) . '%',
            '%' . $wpdb->esc_like($search_query) . '%',
            '%' . $wpdb->esc_like($search_query) . '%'
        );
    }
    $sql .= " ORDER BY appointment_datetime";

    $appointments = $wpdb->get_results($sql, OBJECT);

    ?>
    <div class="wrap">
        <h2>Confirmed Appointments</h2>
        <form method="post">
            <input type="text" name="search_appointment" placeholder="Search appointments..." value="<?php echo esc_attr($search_query); ?>">
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

// Import/Export Page
function custom_scheduler_import_export_page() {
    global $wpdb;
    
    // Handle export
    if (isset($_POST['export_appointments'])) {
        custom_scheduler_export_appointments();
        return;
    }
    
    // Handle import
    if (isset($_POST['import_appointments']) && isset($_FILES['import_file'])) {
        $result = custom_scheduler_import_appointments($_FILES['import_file']);
        if ($result['success']) {
            echo '<div class="updated"><p>' . $result['message'] . '</p></div>';
        } else {
            echo '<div class="error"><p>' . $result['message'] . '</p></div>';
        }
    }
    
    // Handle JSON validation
    if (isset($_POST['validate_json']) && isset($_FILES['validate_file'])) {
        $result = custom_scheduler_validate_json($_FILES['validate_file']);
        if ($result['success']) {
            echo '<div class="updated"><p>' . $result['message'] . '</p></div>';
        } else {
            echo '<div class="error"><p>' . $result['message'] . '</p></div>';
        }
    }
    
    // Get appointment counts for display
    $confirmed_count = $wpdb->get_var("SELECT COUNT(*) FROM " . EVENT_SCHEDULER_TABLE);
    $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM " . EVENT_SCHEDULER_PENDING_TABLE);
    
    ?>
    <div class="wrap">
        <h2>Import/Export Appointments</h2>
        
        <div class="card">
            <h3>Current Data</h3>
            <p><strong>Confirmed Appointments:</strong> <?php echo $confirmed_count; ?></p>
            <p><strong>Pending Appointments:</strong> <?php echo $pending_count; ?></p>
        </div>
        
        <div class="card">
            <h3>Export Appointments</h3>
            <p>Export all confirmed and pending appointments to a JSON file for backup or migration.</p>
            <form method="post" action="">
                <input type="hidden" name="export_appointments" value="1">
                <input type="submit" value="Export All Appointments" class="button-primary">
            </form>
        </div>
        
        <div class="card">
            <h3>Import Appointments</h3>
            <p>Import appointments from a previously exported JSON file. This will add appointments to your current data.</p>
            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="import_appointments" value="1">
                <input type="file" name="import_file" accept=".json" required>
                <br><br>
                <label>
                    <input type="checkbox" name="overwrite_existing" value="1">
                    Overwrite existing appointments (by email and datetime)
                </label>
                <br><br>
                <input type="submit" value="Import Appointments" class="button-primary">
            </form>
        </div>
        
        <div class="card">
            <h3>Validate JSON File</h3>
            <p>Check if a JSON file is valid before importing. This helps identify issues with corrupted or malformed files.</p>
            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="validate_json" value="1">
                <input type="file" name="validate_file" accept=".json" required>
                <br><br>
                <input type="submit" value="Validate JSON File" class="button">
            </form>
        </div>
        
        <div class="card">
            <h3>Instructions</h3>
            <ul>
                <li><strong>Export:</strong> Downloads a JSON file containing all your appointment data</li>
                <li><strong>Import:</strong> Upload a JSON file to restore or migrate appointment data</li>
                <li><strong>Overwrite option:</strong> When checked, will replace existing appointments with the same email and datetime</li>
                <li><strong>Backup recommended:</strong> Always backup your database before importing data</li>
            </ul>
        </div>
    </div>
    
    <style>
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        margin: 20px 0;
        padding: 20px;
    }
    .card h3 {
        margin-top: 0;
    }
    </style>
    <?php
}

// Export appointments function
function custom_scheduler_export_appointments() {
    global $wpdb;
    
    // Prevent any output before headers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Get all confirmed appointments
    $confirmed_appointments = $wpdb->get_results(
        "SELECT * FROM " . EVENT_SCHEDULER_TABLE . " ORDER BY appointment_datetime",
        ARRAY_A
    );
    
    // Get all pending appointments
    $pending_appointments = $wpdb->get_results(
        "SELECT * FROM " . EVENT_SCHEDULER_PENDING_TABLE . " ORDER BY appointment_datetime",
        ARRAY_A
    );
    
    // Prepare export data
    $export_data = [
        'export_info' => [
            'export_date' => current_time('mysql'),
            'plugin_version' => '3.3',
            'wordpress_version' => get_bloginfo('version'),
            'site_url' => get_site_url(),
            'total_confirmed' => count($confirmed_appointments),
            'total_pending' => count($pending_appointments)
        ],
        'confirmed_appointments' => $confirmed_appointments,
        'pending_appointments' => $pending_appointments
    ];
    
    // Generate JSON
    $json_data = json_encode($export_data, JSON_PRETTY_PRINT);
    
    // Validate JSON before sending
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_die('Error generating JSON: ' . json_last_error_msg());
    }
    
    // Set headers for file download
    $filename = 'event-scheduler-export-' . date('Y-m-d-H-i-s') . '.json';
    
    // Clear any existing output
    if (headers_sent()) {
        wp_die('Headers already sent. Cannot download file.');
    }
    
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json_data));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Pragma: no-cache');
    
    // Output JSON data
    echo $json_data;
    exit;
}

// Import appointments function
function custom_scheduler_import_appointments($file) {
    global $wpdb;
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }
    
    if ($file['type'] !== 'application/json' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
        return ['success' => false, 'message' => 'Please upload a valid JSON file.'];
    }
    
    // Read and parse JSON file
    $json_content = file_get_contents($file['tmp_name']);
    
    // Check if file is empty
    if (empty($json_content)) {
        return ['success' => false, 'message' => 'The uploaded file is empty.'];
    }
    
    // Check if file is HTML (common issue)
    if (stripos($json_content, '<!DOCTYPE html') === 0 || 
        stripos($json_content, '<html') === 0 || 
        stripos($json_content, '<head') === 0) {
        return ['success' => false, 'message' => 'The uploaded file appears to be an HTML page instead of a JSON file. This usually happens when the export didn\'t work properly. Please try exporting again from the source server.'];
    }
    
    // Try to decode JSON
    $import_data = json_decode($json_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = 'Invalid JSON file: ' . json_last_error_msg();
        
        // Add more specific error information
        if (json_last_error() === JSON_ERROR_SYNTAX) {
            $error_message .= '<br><br><strong>Common causes:</strong><ul>';
            $error_message .= '<li>File was corrupted during download/upload</li>';
            $error_message .= '<li>File contains invalid characters</li>';
            $error_message .= '<li>File was edited manually and has syntax errors</li>';
            $error_message .= '</ul>';
            $error_message .= '<br><strong>Try:</strong> Re-export the data from the source server and use that fresh file.';
        }
        
        // Show first 500 characters for debugging (in a safe way)
        $preview = substr($json_content, 0, 500);
        $preview = esc_html($preview);
        if (strlen($json_content) > 500) {
            $preview .= '...';
        }
        
        $error_message .= '<br><br><strong>File preview (first 500 characters):</strong><br>';
        $error_message .= '<pre style="background: #f1f1f1; padding: 10px; border: 1px solid #ddd; max-height: 200px; overflow-y: auto;">' . $preview . '</pre>';
        
        return ['success' => false, 'message' => $error_message];
    }
    
    // Validate import data structure
    if (!isset($import_data['confirmed_appointments']) || !isset($import_data['pending_appointments'])) {
        return ['success' => false, 'message' => 'Invalid import file format. Missing required data sections.'];
    }
    
    $overwrite = isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] === '1';
    $imported_confirmed = 0;
    $imported_pending = 0;
    $skipped_confirmed = 0;
    $skipped_pending = 0;
    
    // Import confirmed appointments
    foreach ($import_data['confirmed_appointments'] as $appointment) {
        // Validate required fields
        if (empty($appointment['user_name']) || empty($appointment['email']) || empty($appointment['appointment_datetime'])) {
            continue;
        }
        
        // Check if appointment already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . EVENT_SCHEDULER_TABLE . " WHERE email = %s AND appointment_datetime = %d",
            $appointment['email'],
            $appointment['appointment_datetime']
        ));
        
        if ($exists && !$overwrite) {
            $skipped_confirmed++;
            continue;
        }
        
        // Prepare data for insertion
        $appointment_data = [
            'user_name' => sanitize_text_field($appointment['user_name']),
            'city' => sanitize_text_field($appointment['city'] ?? ''),
            'country' => sanitize_text_field($appointment['country'] ?? ''),
            'appointment_datetime' => intval($appointment['appointment_datetime']),
            'email' => sanitize_email($appointment['email']),
            'cancellation_token' => sanitize_text_field($appointment['cancellation_token'] ?? wp_generate_password(20, false))
        ];
        
        if ($exists && $overwrite) {
            // Update existing appointment
            $wpdb->update(
                EVENT_SCHEDULER_TABLE,
                $appointment_data,
                ['email' => $appointment['email'], 'appointment_datetime' => $appointment['appointment_datetime']]
            );
        } else {
            // Insert new appointment
            $wpdb->insert(EVENT_SCHEDULER_TABLE, $appointment_data);
        }
        
        $imported_confirmed++;
    }
    
    // Import pending appointments
    foreach ($import_data['pending_appointments'] as $appointment) {
        // Validate required fields
        if (empty($appointment['user_name']) || empty($appointment['email']) || empty($appointment['appointment_datetime'])) {
            continue;
        }
        
        // Check if appointment already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . EVENT_SCHEDULER_PENDING_TABLE . " WHERE email = %s AND appointment_datetime = %d",
            $appointment['email'],
            $appointment['appointment_datetime']
        ));
        
        if ($exists && !$overwrite) {
            $skipped_pending++;
            continue;
        }
        
        // Prepare data for insertion
        $appointment_data = [
            'user_name' => sanitize_text_field($appointment['user_name']),
            'city' => sanitize_text_field($appointment['city'] ?? ''),
            'country' => sanitize_text_field($appointment['country'] ?? ''),
            'appointment_datetime' => intval($appointment['appointment_datetime']),
            'email' => sanitize_email($appointment['email']),
            'verification_token' => sanitize_text_field($appointment['verification_token'] ?? wp_generate_password(20, false)),
            'cancellation_token' => sanitize_text_field($appointment['cancellation_token'] ?? wp_generate_password(20, false)),
            'repeat_type' => sanitize_text_field($appointment['repeat_type'] ?? null),
            'end_date' => !empty($appointment['end_date']) ? intval($appointment['end_date']) : null
        ];
        
        if ($exists && $overwrite) {
            // Update existing appointment
            $wpdb->update(
                EVENT_SCHEDULER_PENDING_TABLE,
                $appointment_data,
                ['email' => $appointment['email'], 'appointment_datetime' => $appointment['appointment_datetime']]
            );
        } else {
            // Insert new appointment
            $wpdb->insert(EVENT_SCHEDULER_PENDING_TABLE, $appointment_data);
        }
        
        $imported_pending++;
    }
    
    $message = sprintf(
        'Import completed! Confirmed: %d imported, %d skipped. Pending: %d imported, %d skipped.',
        $imported_confirmed,
        $skipped_confirmed,
        $imported_pending,
        $skipped_pending
    );
    
    return ['success' => true, 'message' => $message];
}

// Validate JSON file function
function custom_scheduler_validate_json($file) {
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }
    
    if ($file['type'] !== 'application/json' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
        return ['success' => false, 'message' => 'Please upload a valid JSON file.'];
    }
    
    // Read and parse JSON file
    $json_content = file_get_contents($file['tmp_name']);
    
    // Check if file is empty
    if (empty($json_content)) {
        return ['success' => false, 'message' => 'The uploaded file is empty.'];
    }
    
    // Check if file is HTML (common issue)
    if (stripos($json_content, '<!DOCTYPE html') === 0 || 
        stripos($json_content, '<html') === 0 || 
        stripos($json_content, '<head') === 0) {
        return ['success' => false, 'message' => 'The uploaded file appears to be an HTML page instead of a JSON file. This usually happens when the export didn\'t work properly. Please try exporting again from the source server.'];
    }
    
    // Try to decode JSON
    $import_data = json_decode($json_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = '<strong>JSON Validation Failed:</strong><br>';
        $error_message .= 'Error: ' . json_last_error_msg() . '<br><br>';
        
        // Show character position if available
        $error_position = json_last_error_msg();
        if (preg_match('/position (\d+)/', $error_position, $matches)) {
            $position = intval($matches[1]);
            $start = max(0, $position - 50);
            $end = min(strlen($json_content), $position + 50);
            $context = substr($json_content, $start, $end - $start);
            $context = esc_html($context);
            $error_message .= '<strong>Error around position ' . $position . ':</strong><br>';
            $error_message .= '<pre style="background: #f1f1f1; padding: 10px; border: 1px solid #ddd;">' . $context . '</pre>';
        }
        
        return ['success' => false, 'message' => $error_message];
    }
    
    // Check if it's a valid export file
    $is_export_file = isset($import_data['export_info']) && 
                      isset($import_data['confirmed_appointments']) && 
                      isset($import_data['pending_appointments']);
    
    if ($is_export_file) {
        $confirmed_count = count($import_data['confirmed_appointments']);
        $pending_count = count($import_data['pending_appointments']);
        $export_date = $import_data['export_info']['export_date'] ?? 'Unknown';
        $site_url = $import_data['export_info']['site_url'] ?? 'Unknown';
        
        $message = '<strong>✅ Valid Event Scheduler Export File</strong><br><br>';
        $message .= '<strong>Export Details:</strong><br>';
        $message .= '• Export Date: ' . esc_html($export_date) . '<br>';
        $message .= '• Source Site: ' . esc_html($site_url) . '<br>';
        $message .= '• Confirmed Appointments: ' . $confirmed_count . '<br>';
        $message .= '• Pending Appointments: ' . $pending_count . '<br><br>';
        $message .= 'This file is ready to import!';
        
        return ['success' => true, 'message' => $message];
    } else {
        return ['success' => false, 'message' => 'This is a valid JSON file, but it doesn\'t appear to be an Event Scheduler export file. Make sure you\'re using a file exported from this plugin.'];
    }
}

?>
