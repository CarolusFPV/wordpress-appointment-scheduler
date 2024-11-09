<?php
/*
Plugin Name: Event Scheduler
Description: Allow users to schedule a timeframe to take part of an event. The entire schedule is displayed in the calendar shortcode.
Version: 2.6
Author: Casper Molhoek
Current bugs:
    - Upon clicking the link in the appointment verification email it opens an URL that just says 0, this also doesn't actually verifiy the appointment.
      It should instead open the same page the appliction form was sent from. The [event_scheduler] shortcode should then open and throw an alert message that the appointment was scheduled succesfully
    - Upon sumbitting the form the form menu should close and above the callender view a message should pop up that the application was sent successfully and to verify that they need to click the link sent to their email.
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
            PRIMARY KEY (id)
        ) $charset_collate;"
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

    // Log incoming data for debugging
    error_log('Incoming POST data: ' . print_r($_POST, true));

    // Sanitize and assign form fields
    $user_name = sanitize_text_field($_POST['user_name']);
    $city = sanitize_text_field($_POST['city']);
    $country = sanitize_text_field($_POST['country']);
    $email = sanitize_email($_POST['email']);
    $unix_timestamp = sanitize_text_field($_POST['unix_timestamp']);

    // Prepare email verification link
    $verification_token = wp_generate_password(20, false); // Generate a random token
    $verification_url = add_query_arg(
        [
            'verify_appointment' => 1,
            'token' => $verification_token
        ],
        home_url()
    );

    // Insert appointment into pending table
    $result = $wpdb->insert(
        EVENT_SCHEDULER_PENDING_TABLE,
        [
            'user_name' => $user_name,
            'city' => $city,
            'country' => $country,
            'appointment_datetime' => $unix_timestamp,
            'email' => $email,
            'verification_token' => $verification_token
        ]
    );

    // Check the result of the insert operation
    if ($result === false) {
        error_log('Database insert error: ' . $wpdb->last_error);
        wp_send_json_error('Database insert error.');
        return;
    }

    // Send verification email with clickable link
    $email_sent = wp_mail($email, 'Appointment Verification', "Please verify your appointment here: " . $verification_url);

    if ($email_sent) {
        wp_send_json_success(); // Return success response
    } else {
        wp_send_json_error('Email sending failed.');
    }
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'event_scheduler_enqueue_scripts');
function event_scheduler_enqueue_scripts() {
    wp_enqueue_script('scheduler-loader', plugins_url('/js/scheduler-loader.js', __FILE__), array('jquery'), null, true);

    wp_localize_script('scheduler-loader', 'scheduler_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'), // Correctly set AJAX URL
        'interval' => get_option('event_scheduler_interval', 60)
    ));
}
