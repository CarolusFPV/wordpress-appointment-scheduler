<?php
/*
Plugin Name: Event Scheduler
Description: Allow users to schedule a timeframe to take part of an event. The entire schedule is displayed in the [event_scheduler] shortcode.
Version: 3.1
Author: Casper Molhoek
 
Todo:
- Make sure server denies attempts at rescheduling a verified appointment
- Fix admin menu appointment search function
- Add feature to allow an appointment to automatically be placed weekly, in a given period up to a year
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
            PRIMARY KEY (id)
        ) $charset_collate;",        
    ];

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
}

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
add_action('wp_ajax_nopriv_submit_appointment', 'submit_appointment');
add_action('wp_ajax_submit_appointment', 'submit_appointment');

function submit_appointment() {
    global $wpdb;

    // Sanitize and assign form fields
    $user_name = sanitize_text_field($_POST['user_name']);
    $city = sanitize_text_field($_POST['city']);
    $country = sanitize_text_field($_POST['country']);
    $email = sanitize_email($_POST['email']);
    $unix_timestamp = sanitize_text_field($_POST['unix_timestamp']);
    $local_datetime = sanitize_text_field($_POST['local_datetime']);
    $page_url = sanitize_text_field($_POST['page_url']);

    // Remove any query parameters from the base page URL
    $base_url = strtok($page_url, '?');

    // Prepare email verification link with page_url as a parameter
    $verification_token = wp_generate_password(20, false);
    $verification_url = add_query_arg(
        [
            'verify_appointment' => 1,
            'token' => $verification_token
        ],
        $page_url
    );

    $cancellation_token = wp_generate_password(20, false);

    $wpdb->insert(
        EVENT_SCHEDULER_PENDING_TABLE,
        [
            'user_name' => $user_name,
            'city' => $city,
            'country' => $country,
            'appointment_datetime' => $unix_timestamp,
            'email' => $email,
            'verification_token' => $verification_token,
            'cancellation_token' => $cancellation_token
        ]
    );

    if ($result === false) {
        $error_message = get_message_template('appointment_submit_failed', [
            'name' => $user_name,
            'time' => $local_datetime,
            'city' => $city,
            'country' => $country,
            'email' => $email,
            'error message' => $wpdb->last_error
        ]);
        wp_send_json_error(['message' => $error_message]);
        return;
    }

    $cancellation_url = add_query_arg(
        [
            'cancel_appointment' => 1,
            'token' => $cancellation_token
        ],
        $page_url
    );

    // Send verification email
    $email_subject = "Bevestiging van uw aanmelding";
    $email_message = get_message_template('appointment_email_verification', [
        'name' => $user_name,
        'time' => $local_datetime,
        'city' => $city,
        'country' => $country,
        'email' => $email,
        'verify_url' => $verification_url,
        'cancel_url' => $cancellation_url
    ]);

    $email_sent = wp_mail($email, $email_subject, $email_message);

    if ($email_sent) {
        // Load success message from the admin settings
        $success_message = get_message_template('appointment_submit_success', [
            'name' => $user_name,
            'time' => $local_datetime,
            'city' => $city,
            'country' => $country,
            'email' => $email
        ]);

        wp_send_json_success(['message' => $success_message]);
    } else {
        wp_send_json_error(['message' => 'E-mail kon niet worden verzonden.']);
    }
}

// Handle verification link click
add_action('init', 'handle_appointment_verification');

function handle_appointment_verification() {
    if (isset($_GET['verify_appointment']) && $_GET['verify_appointment'] == 1 && isset($_GET['token'])) {
        global $wpdb;
        $token = sanitize_text_field($_GET['token']);

        // Get the current page URL
        $current_url = add_query_arg(null, null, home_url(add_query_arg([], $wp->request)));

        // Retrieve the pending appointment using the token
        $appointment = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM " . EVENT_SCHEDULER_PENDING_TABLE . " WHERE verification_token = %s", $token)
        );

        if ($appointment) {
            // Move the appointment to the confirmed table
            $wpdb->insert(
                EVENT_SCHEDULER_TABLE,
                [
                    'user_name' => $appointment->user_name,
                    'city' => $appointment->city,
                    'country' => $appointment->country,
                    'appointment_datetime' => $appointment->appointment_datetime,
                    'email' => $appointment->email,
                    'cancellation_token' => $appointment->cancellation_token
                ]
            );

            // Remove the appointment from the pending table
            $wpdb->delete(
                EVENT_SCHEDULER_PENDING_TABLE,
                ['id' => $appointment->id]
            );

            // Redirect to the current page URL with a success parameter
            wp_redirect(add_query_arg('verified', 'success', $current_url));
            exit;
        } else {
            // Redirect to the current page URL with an error parameter if the token is invalid
            wp_redirect(add_query_arg('verified', 'error', $current_url));
            exit;
        }
    }
}


add_action('init', 'handle_appointment_cancellation');

function handle_appointment_cancellation() {
    if (isset($_GET['cancel_appointment']) && $_GET['cancel_appointment'] == 1 && isset($_GET['token'])) {
        global $wpdb;
        $token = sanitize_text_field($_GET['token']);

        // Retrieve the appointment using the cancellation token
        $appointment = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM " . EVENT_SCHEDULER_TABLE . " WHERE cancellation_token = %s", $token)
        );

        if ($appointment) {
            // Delete the confirmed appointment
            $wpdb->delete(
                EVENT_SCHEDULER_TABLE,
                ['id' => $appointment->id]
            );

            // Display cancellation message
            $cancel_message = get_message_template('appointment_cancellation', []);
            echo esc_html($cancel_message);
            exit;
        } else {
            echo '<h1>Invalid Cancellation Link</h1>';
            echo '<p>We couldn’t find your appointment. It may have already been cancelled or the link may be invalid.</p>';
            exit;
        }
    }
}

// Helper function to retrieve a message template with replaced placeholders
function get_message_template($template_name, $placeholders = []) {
    $template = get_option($template_name . '_template', '');
    foreach ($placeholders as $placeholder => $value) {
        $template = str_replace('%' . $placeholder . '%', $value, $template);
    }
    return $template;
}