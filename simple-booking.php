<?php
/**
 * Plugin Name: Simple Booking Plugin
 * Description: Version 1.6 with same-page confirmation fix and proper form handling.
 * Version: 1.6
 * Author: Miguel Barroso
 */

// Prevent direct access for security.
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue styles and scripts.
function simple_booking_enqueue_scripts() {
    wp_enqueue_style('simple-booking-style', 'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css');
}
add_action('wp_enqueue_scripts', 'simple_booking_enqueue_scripts');

// Shortcode for booking form with same-page submission.
function simple_booking_form() {
    ob_start(); ?>
    <form id="simple-booking-form" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
        <?php wp_nonce_field('simple_booking_action', 'simple_booking_nonce'); ?>
        
        <label for="name">Namn:</label>
        <input type="text" id="name" name="name" placeholder="Ditt namn" required>

        <label for="email">E-post:</label>
        <input type="email" id="email" name="email" placeholder="Din e-post" required>

        <label for="message">Meddelande:</label>
        <textarea id="message" name="message" placeholder="Skriv ett meddelande"></textarea>

        <label for="start-date">Startdatum:</label>
        <input type="date" id="start-date" name="start_date" required onchange="calculateNights()">

        <label for="end-date">Slutdatum:</label>
        <input type="date" id="end-date" name="end_date" required onchange="calculateNights()">

        <p id="night-cost">Pris: 0 kr</p>

        <button type="submit">Boka</button>
    </form>

    <script>
        function calculateNights() {
            const startValue = document.getElementById('start-date').value;
            const endValue = document.getElementById('end-date').value;
            if (!startValue || !endValue) {
                document.getElementById('night-cost').textContent = 'Pris: 0 kr';
                return;
            }
            
            const start = new Date(startValue);
            const end = new Date(endValue);
            const nights = Math.max(0, Math.floor((end - start) / (1000 * 60 * 60 * 24)));
            const cost = isNaN(nights) ? 0 : nights * 299;
            
            document.getElementById('night-cost').textContent = `Pris: ${cost} kr (${nights} nätter)`;
        }
    </script>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && wp_verify_nonce($_POST['simple_booking_nonce'], 'simple_booking_action')) {
        echo '<p style="color:green;">Tack för din bokning! Vi återkommer till dig inom kort.</p>';
    }
    return ob_get_clean();
}
add_shortcode('simple_booking', 'simple_booking_form');

// Handle form submission and send an email.
function simple_booking_process() {
    if (!wp_verify_nonce($_POST['simple_booking_nonce'], 'simple_booking_action')) {
        wp_die('Ogiltig begäran.');
    }

    $page_title = get_the_title();
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $message_content = sanitize_textarea_field($_POST['message']);
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);

    $nights = max(0, (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24));
    $total_price = $nights * 299;

    $to = get_option('admin_email');
    $subject = "Bokningsförfrågan från $page_title";
    $message = "Bokningsinformation:\n\n" .
               "Namn: $name\n" .
               "E-post: $email\n" .
               "Meddelande: $message_content\n" .
               "Period: $start_date till $end_date\n" .
               "Antal nätter: $nights\n" .
               "Pris: $total_price kr\n";
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    wp_mail($to, $subject, $message, $headers);
}
add_action('admin_post_simple_booking', 'simple_booking_process');
add_action('admin_post_nopriv_simple_booking', 'simple_booking_process');
