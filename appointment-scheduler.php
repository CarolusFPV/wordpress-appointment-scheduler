<?php
/*
Plugin Name: Event Scheduler
Description: Allow users to schedule a timeframe to take part of an event. The entire schedule is displayed in the [event_scheduler] shortcode.
Version: 3.2
Author: Casper Molhoek
 
Todo:
- Fix admin menu appointment search function
*/

global $wpdb;
define('EVENT_SCHEDULER_TABLE', $wpdb->prefix . 'event_scheduler_appointments');
define('EVENT_SCHEDULER_PENDING_TABLE', $wpdb->prefix . 'event_scheduler_pending');

// Include admin and shortcodes logic
if (is_admin()) {
    include(plugin_dir_path(__FILE__) . 'admin.php');
}
include(plugin_dir_path(__FILE__) . 'shortcodes.php');

// Start PHP sessions if not started
add_action('init', function() {
    if (!session_id()) session_start();
});

// Create database tables on plugin activation
register_activation_hook(__FILE__, 'event_scheduler_create_tables');
function event_scheduler_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $tables = [
        EVENT_SCHEDULER_TABLE => "CREATE TABLE IF NOT EXISTS " . EVENT_SCHEDULER_TABLE . " (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            user_name VARCHAR(100) NOT NULL,
            city VARCHAR(100) NOT NULL,
            country VARCHAR(100) NOT NULL,
            appointment_datetime INT(11) NOT NULL,
            email VARCHAR(100) NOT NULL,
            cancellation_token VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;",
        EVENT_SCHEDULER_PENDING_TABLE => "CREATE TABLE IF NOT EXISTS " . EVENT_SCHEDULER_PENDING_TABLE . " (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            user_name VARCHAR(100) NOT NULL,
            city VARCHAR(100) NOT NULL,
            country VARCHAR(100) NOT NULL,
            appointment_datetime INT(11) NOT NULL,
            email VARCHAR(100) NOT NULL,
            verification_token VARCHAR(255) NOT NULL,
            cancellation_token VARCHAR(255) NOT NULL,
            repeat_type VARCHAR(20) DEFAULT NULL,
            end_date INT(11) DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;",
    ];

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
}

// Update database tables to add missing columns
function event_scheduler_update_tables() {
    global $wpdb;

    // Add missing columns to EVENT_SCHEDULER_PENDING_TABLE
    $alter_table_sql = "
        ALTER TABLE " . EVENT_SCHEDULER_PENDING_TABLE . "
        ADD COLUMN IF NOT EXISTS repeat_type VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS end_date INT(11) DEFAULT NULL;
    ";

    // Execute the SQL query
    $wpdb->query($alter_table_sql);
}
add_action('init', 'event_scheduler_update_tables');

// AJAX handler for fetching full schedule
add_action('wp_ajax_get_schedule', 'get_schedule');
add_action('wp_ajax_nopriv_get_schedule', 'get_schedule');

function get_schedule() {
    global $wpdb;

    // Get the selected date from the AJAX request
    $selected_date = sanitize_text_field($_POST['selected_date']);
    error_log("Selected date for fetching appointments: " . $selected_date); // Logging the selected date

    // Query the database for appointments on the specific date
    $appointments = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_name, city, country, appointment_datetime 
             FROM " . EVENT_SCHEDULER_TABLE . " 
             WHERE DATE(FROM_UNIXTIME(appointment_datetime)) = %s", 
            $selected_date
        ), 
        ARRAY_A
    );

    // Return the results in JSON format
    wp_send_json_success(['appointments' => $appointments]); // Return appointments as part of the response
    wp_die();
}

// Handle form submission
function submit_appointment() {
    global $wpdb;

    // Sanitize and assign form fields
    $user_name = sanitize_text_field($_POST['user_name']);
    $city = sanitize_text_field($_POST['city']);
    $country = sanitize_text_field($_POST['country']);
    $email = sanitize_email($_POST['email']);
    $unix_timestamp = (int) sanitize_text_field($_POST['unix_timestamp']);
    $local_datetime = sanitize_text_field($_POST['local_datetime']);
    $page_url = sanitize_text_field($_POST['page_url']);

    // Optional repeat fields
    $repeat_type = isset($_POST['repeat_type']) ? sanitize_text_field($_POST['repeat_type']) : null;
    $end_date = isset($_POST['end_date']) ? (int) sanitize_text_field($_POST['end_date']) : null;

    // Remove any query parameters from the base page URL
    $base_url = strtok($page_url, '?');

    // Generate tokens
    $verification_token = wp_generate_password(20, false);
    $cancellation_token = wp_generate_password(20, false);

    // Insert the initial appointment into the pending table
    $result = $wpdb->insert(
        EVENT_SCHEDULER_PENDING_TABLE,
        [
            'user_name' => $user_name,
            'city' => $city,
            'country' => $country,
            'appointment_datetime' => $unix_timestamp,
            'email' => $email,
            'verification_token' => $verification_token,
            'cancellation_token' => $cancellation_token,
            'repeat_type' => $repeat_type,
            'end_date' => $end_date,
        ]
    );

    if ($result === false) {
        $error_message = get_message_template('appointment_submit_failed', [
            'name' => $user_name,
            'time' => $local_datetime,
            'city' => $city,
            'country' => $country,
            'email' => $email,
            'error_message' => $wpdb->last_error,
        ]);
        wp_send_json_error(['message' => $error_message]);
        return;
    }

    // Prepare verification and cancellation URLs
    $verification_url = add_query_arg(
        [
            'verify_appointment' => 1,
            'token' => $verification_token,
        ],
        $page_url
    );

    $cancellation_url = add_query_arg(
        [
            'cancel_appointment' => 1,
            'token' => $cancellation_token,
        ],
        $page_url
    );

    // Send email using a separate method
    $email_sent = send_appointment_email($email, $user_name, $local_datetime, $city, $country, $repeat_type, $end_date, $verification_url, $cancellation_url);

    if ($email_sent) {
        $success_message = get_message_template('appointment_submit_success', [
            'name' => $user_name,
            'time' => $local_datetime,
            'city' => $city,
            'country' => $country,
            'email' => $email,
            'repeat_type' => $repeat_type ? ($repeat_type === 'daily' ? 'Dagelijks' : 'Wekelijks') : 'Geen',
            'end_date' => $end_date ? date('Y-m-d', $end_date) : 'N/A',
        ]);

        wp_send_json_success(['message' => $success_message]);
    } else {
        wp_send_json_error(['message' => 'E-mail kon niet worden verzonden. Probeer het opnieuw.']);
    }
}
add_action('wp_ajax_nopriv_submit_appointment', 'submit_appointment');
add_action('wp_ajax_submit_appointment', 'submit_appointment');

// Separate method for sending email
function send_appointment_email($to, $name, $time, $city, $country, $repeat_type, $end_date, $verify_url, $cancel_url) {
    $email_subject = "Bevestiging van uw aanmelding";
    $email_message = get_message_template('appointment_email_verification', [
        'name' => $name,
        'time' => $time,
        'city' => $city,
        'country' => $country,
        'repeat_type' => $repeat_type ? ($repeat_type === 'daily' ? 'Dagelijks' : 'Wekelijks') : 'Geen',
        'end_date' => $end_date ? date('Y-m-d', $end_date) : 'N/A',
        'verify_url' => $verify_url,
        'cancel_url' => $cancel_url,
    ]);

    return wp_mail($to, $email_subject, $email_message);
}


// Handle verification link click
function handle_appointment_verification() {
    if (isset($_GET['verify_appointment']) && $_GET['verify_appointment'] == 1 && isset($_GET['token'])) {
        global $wpdb;

        $token = sanitize_text_field($_GET['token']);

        // Get the current page URL
        $page_url = strtok($_SERVER['REQUEST_URI'], '?');

        // Retrieve the pending appointment using the token
        $appointment = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM " . EVENT_SCHEDULER_PENDING_TABLE . " WHERE verification_token = %s", $token)
        );

        if ($appointment) {
            $cancellation_token = $appointment->cancellation_token;
            $repeat_type = $appointment->repeat_type;
            $end_date = $appointment->end_date;

            // Check if the appointment already exists
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM " . EVENT_SCHEDULER_TABLE . " WHERE appointment_datetime = %d AND email = %s",
                    $appointment->appointment_datetime,
                    $appointment->email
                )
            );

            // Schedule the initial appointment if it doesn't exist
            if (!$exists) {
                $wpdb->insert(
                    EVENT_SCHEDULER_TABLE,
                    [
                        'user_name' => $appointment->user_name,
                        'city' => $appointment->city,
                        'country' => $appointment->country,
                        'appointment_datetime' => $appointment->appointment_datetime,
                        'email' => $appointment->email,
                        'cancellation_token' => $cancellation_token,
                    ]
                );
            }

            // Schedule additional appointments if repeat is enabled
            if ($repeat_type && $end_date) {
                $current_date = (int) $appointment->appointment_datetime;

                while ($current_date < $end_date) {
                    $current_date = $repeat_type === 'daily'
                        ? strtotime('+1 day', $current_date)
                        : strtotime('+1 week', $current_date);

                    if ($current_date >= $end_date) break;

                    // Check if the appointment already exists
                    $exists = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM " . EVENT_SCHEDULER_TABLE . " WHERE appointment_datetime = %d AND email = %s",
                            $current_date,
                            $appointment->email
                        )
                    );

                    // Schedule the appointment if it doesn't exist
                    if (!$exists) {
                        $wpdb->insert(
                            EVENT_SCHEDULER_TABLE,
                            [
                                'user_name' => $appointment->user_name,
                                'city' => $appointment->city,
                                'country' => $appointment->country,
                                'appointment_datetime' => $current_date,
                                'email' => $appointment->email,
                                'cancellation_token' => $cancellation_token,
                            ]
                        );
                    }
                }
            }

            // Remove the appointment from the pending table
            $wpdb->delete(
                EVENT_SCHEDULER_PENDING_TABLE,
                ['id' => $appointment->id]
            );

            wp_redirect(add_query_arg('verified', 'success', $page_url));
            exit;
        } else {
            wp_redirect(add_query_arg('verified', 'error', $page_url));
            exit;
        }
    }
}
add_action('init', 'handle_appointment_verification');


function handle_appointment_cancellation() {
    if (isset($_GET['cancel_appointment']) && $_GET['cancel_appointment'] == 1 && isset($_GET['token'])) {
        global $wpdb;

        $token = sanitize_text_field($_GET['token']);

        // Retrieve all appointments with the same cancellation token
        $appointments = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM " . EVENT_SCHEDULER_TABLE . " WHERE cancellation_token = %s", $token)
        );

        if ($appointments) {
            // Delete all linked appointments
            $wpdb->query(
                $wpdb->prepare("DELETE FROM " . EVENT_SCHEDULER_TABLE . " WHERE cancellation_token = %s", $token)
            );

            // Display cancellation success message
            $cancel_message = get_message_template('appointment_cancellation_success', []);
            echo esc_html($cancel_message);
            exit;
        } else {
            echo '<h1>Invalid Cancellation Link</h1>';
            echo '<p>We couldn’t find your appointment. It may have already been cancelled or the link may be invalid.</p>';
            exit;
        }
    }
}
add_action('init', 'handle_appointment_cancellation');


// Helper function to retrieve a message template with replaced placeholders
function get_message_template($template_name, $placeholders = []) {
    $template = get_option($template_name . '_template', '');
    foreach ($placeholders as $placeholder => $value) {
        $template = str_replace('%' . $placeholder . '%', $value, $template);
    }
    return $template;
}