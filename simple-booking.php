<?php
/**
 * Plugin Name: Simple Booking Plugin
 * Description: A simple booking plugin with name, email, message fields, start/end date pickers, and email notification.
 * Version: 1.2
 * Author: Miguel Barroso
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Use HTML5 native datepicker without jQuery
function simple_booking_enqueue_scripts() {
    wp_enqueue_style('simple-booking-style', 'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css');
}
add_action('wp_enqueue_scripts', 'simple_booking_enqueue_scripts');

// Shortcode for the booking form
function simple_booking_form() {
    ob_start(); ?>
    <form id="simple-booking-form" method="post">
        <label for="name">Namn:</label>
        <input type="text" id="name" name="name" placeholder="Ditt namn" required>

        <label for="email">E-post:</label>
        <input type="email" id="email" name="email" placeholder="Din e-post" required>

        <label for="message">Meddelande:</label>
        <textarea id="message" name="message" placeholder="Skriv ett meddelande"></textarea>

        <label for="start-date">Startdatum:</label>
        <input type="date" id="start-date" name="start_date" pattern="\\d{4}-\\d{2}-\\d{2}" placeholder="ÅÅÅÅ-MM-DD" required>

        <label for="end-date">Slutdatum:</label>
        <input type="date" id="end-date" name="end_date" pattern="\\d{4}-\\d{2}-\\d{2}" placeholder="ÅÅÅÅ-MM-DD" required>

        <button type="submit">Boka</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('simple_booking', 'simple_booking_form');

// Handle form submission and send email
function simple_booking_handle_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['email'], $_POST['message'], $_POST['start_date'], $_POST['end_date'])) {
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $message_content = sanitize_textarea_field($_POST['message']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        // Email details in Swedish
        $to = get_option('admin_email');
        $subject = 'Ny Bokningsförfrågan';
        $message = "En ny bokningsförfrågan har inkommit:\n\n" .
                   "Namn: $name\n" .
                   "E-post: $email\n" .
                   "Meddelande: $message_content\n" .
                   "Period: $start_date till $end_date";
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        // Send email
        if (wp_mail($to, $subject, $message, $headers)) {
            echo '<p style="color:green;">Bokningsförfrågan skickades!</p>';
        } else {
            echo '<p style="color:red;">Det gick inte att skicka bokningsförfrågan.</p>';
        }
    }
}
add_action('wp_footer', 'simple_booking_handle_submission');
