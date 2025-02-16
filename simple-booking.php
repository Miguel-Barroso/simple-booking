<?php
/**
 * Plugin Name: Simple Booking Plugin
 * Description: Booking plugin with name, email, message fields, start/end dates, and proper form submission handling.
 * Version: 1.4
 * Author: Miguel Barroso
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue styles
function simple_booking_enqueue_scripts() {
    wp_enqueue_style('simple-booking-style', 'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css');
}
add_action('wp_enqueue_scripts', 'simple_booking_enqueue_scripts');

// Shortcode for booking form
function simple_booking_form() {
    ob_start(); ?>
    <form id="simple-booking-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="simple_booking">
        
        <label for="name">Namn:</label>
        <input type="text" id="name" name="name" placeholder="Ditt namn" required>

        <label for="email">E-post:</label>
        <input type="email" id="email" name="email" placeholder="Din e-post" required>

        <label for="message">Meddelande:</label>
        <textarea id="message" name="message" placeholder="Skriv ett meddelande"></textarea>

        <label for="start-date">Startdatum:</label>
        <input type="date" id="start-date" name="start_date" required>

        <label for="end-date">Slutdatum:</label>
        <input type="date" id="end-date" name="end_date" required>

        <button type="submit">Boka</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('simple_booking', 'simple_booking_form');

// Handle form submission using admin_post
function simple_booking_process() {
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $message_content = sanitize_textarea_field($_POST['message']);
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);

    $to = get_option('admin_email');
    $subject = 'Ny Bokningsförfrågan';
    $message = "Namn: $name\nE-post: $email\nMeddelande: $message_content\nPeriod: $start_date till $end_date";
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    wp_mail($to, $subject, $message, $headers);
    wp_safe_redirect(home_url('/tack-for-din-bokning/'));
    exit;
}
add_action('admin_post_simple_booking', 'simple_booking_process');
add_action('admin_post_nopriv_simple_booking', 'simple_booking_process');
