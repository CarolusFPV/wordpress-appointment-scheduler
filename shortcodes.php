<?php
// Shortcode to display the event scheduler
function event_scheduler_combined_shortcode() {
    global $wpdb;
    $output = '';

    // Add custom CSS to the output
    $scheduler_css = get_option('event_scheduler_css', '');
    $output .= '<style type="text/css">' . esc_html($scheduler_css) . '</style>';

    // Load the calendar view
    ob_start();
    include(plugin_dir_path(__FILE__) . 'templates/calendar-view.php');  // Load the calendar template
    $output .= ob_get_clean();

    // Include the appointment form template
    ob_start();
    include(plugin_dir_path(__FILE__) . 'templates/appointment-form.php');
    $output .= ob_get_clean();

    // Inject the script directly into the shortcode output
    $script_url = plugins_url('/js/scheduler-loader.js', __FILE__);
    $localized_data = json_encode(array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'interval' => get_option('event_scheduler_interval', 60),
    ));

    $output .= <<<HTML
<script src="$script_url"></script>
<script>
    var scheduler_data = $localized_data;
</script>
HTML;

    return $output;
}

// Register the shortcode
add_shortcode('event_scheduler', 'event_scheduler_combined_shortcode');
