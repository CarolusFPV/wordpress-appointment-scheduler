<?php
/*
Plugin Name: Event Scheduler
Description: Allow users to schedule a timeframe to take part of an event. The entire schedule is displayed in the [event_scheduler] shortcode.
Version: 3.0
Author: Casper Molhoek
 
Todo:
- Upon submitting a form, display a message about clicking the link in the email
- Turn all messages and emails into a template based system for easy costumization
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

// Handle form submission
function submit_appointment() {
    global $wpdb;

    // Sanitize and assign form fields
    $user_name = sanitize_text_field($_POST['user_name']);
    $city = sanitize_text_field($_POST['city']);
    $country = sanitize_text_field($_POST['country']);
    $email = sanitize_email($_POST['email']);
    $unix_timestamp = sanitize_text_field($_POST['unix_timestamp']);
    $local_datetime = sanitize_text_field($_POST['local_datetime']); // Retrieve local date and time for email
    $page_url = sanitize_text_field($_POST['page_url']); // Retrieve the page URL

    // Prepare email verification link with page_url as a parameter
    $verification_token = wp_generate_password(20, false); // Generate a random token
    $verification_url = add_query_arg(
        [
            'verify_appointment' => 1,
            'token' => $verification_token,
            'page_url' => urlencode($page_url) // Pass page_url in the link
        ],
        $page_url // Use the captured page URL
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

    // Send verification email with Dutch message and local time
    $email_subject = "Bevestiging van uw aanmelding";
    $email_message = "Beste $user_name,

    Hartelijk dank voor uw aanmelding! 
    Gelieve op de onderstaande link te klikken om uw aanmelding te bevestigen.

    Afspraakgegevens:
    - Naam: $user_name
    - Stad: $city
    - Land: $country
    - Datum en tijd: $local_datetime

    Link om te bevestigen: $verification_url

    Mocht u vragen hebben of de afspraak willen wijzigen, neem dan gerust contact op.

    Met vriendelijke groet";

    $email_sent = wp_mail($email, $email_subject, $email_message);

    if ($email_sent) {
        wp_send_json_success(); // Return success response
    } else {
        wp_send_json_error('E-mail kon niet worden verzonden.');
    }
}

// Handle verification link click
add_action('init', 'handle_appointment_verification');

function handle_appointment_verification() {
    if (isset($_GET['verify_appointment']) && $_GET['verify_appointment'] == 1 && isset($_GET['token'])) {
        global $wpdb;
        $token = sanitize_text_field($_GET['token']);
        $page_url = isset($_GET['page_url']) ? urldecode($_GET['page_url']) : home_url();

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
                    'email' => $appointment->email
                ]
            );

            // Remove the appointment from the pending table
            $wpdb->delete(
                EVENT_SCHEDULER_PENDING_TABLE,
                ['id' => $appointment->id]
            );

            // Redirect to the specific page with success parameter
            wp_redirect(add_query_arg('verified', 'success', $page_url));
            exit;
        } else {
            // Redirect to the specific page with error parameter if token is invalid
            wp_redirect(add_query_arg('verified', 'error', $page_url));
            exit;
        }
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
