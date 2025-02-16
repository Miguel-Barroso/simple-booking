<?php
/**
 * Plugin Name: Simple Booking Plugin
 * Description: Version 1.6 with same-page confirmation fix, proper form handling, and email fix.
 * Version: 1.6.1
 * Author: Miguel Barroso
 */

if (!defined('ABSPATH')) {
    exit;
}

function simple_booking_enqueue_scripts() {
    wp_enqueue_style('simple-booking-style', 'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css');
}
add_action('wp_enqueue_scripts', 'simple_booking_enqueue_scripts');

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
            const start = new Date(document.getElementById('start-date').value);
            const end = new Date(document.getElementById('end-date').value);
            if (!start.getTime() || !end.getTime()) {
                document.getElementById('night-cost').textContent = 'Pris: 0 kr';
                return;
            }
            const nights = Math.max(0, (end - start) / (1000 * 60 * 60 * 24));
            document.getElementById('night-cost').textContent = `Pris: ${nights * 299} kr (${nights} nätter)`;
        }
    </script>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && wp_verify_nonce($_POST['simple_booking_nonce'], 'simple_booking_action')) {
        echo '<p style="color:green;">Tack för din bokning! Vi återkommer till dig inom kort.</p>';
        simple_booking_process();
    }
    return ob_get_clean();
}
add_shortcode('simple_booking', 'simple_booking_form');

function simple_booking_process() {
    if (!isset($_POST['simple_booking_nonce']) || !wp_verify_nonce($_POST['simple_booking_nonce'], 'simple_booking_action')) {
        echo '<p style="color:red;">Ogiltig begäran.</p>';
        return;
    }

    $page_title = get_the_title();
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $message_content = sanitize_textarea_field($_POST['message']);
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);

    $nights = max(0, (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24));
    $total_price = $nights * 299;

    add_filter('wp_mail_content_type', function() { return 'text/plain'; });
    wp_mail(get_option('admin_email'), "Bokningsförfrågan från $page_title", "Namn: $name\nE-post: $email\nMeddelande: $message_content\nPeriod: $start_date till $end_date\nAntal nätter: $nights\nPris: $total_price kr");
    remove_filter('wp_mail_content_type', function() { return 'text/plain'; });
}
