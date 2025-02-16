<?php
/**
 * Plugin Name: Simple Booking Plugin
 * Description: Version 1.6.2 with code comments explaining all functionality and logic.
 * Version: 1.6.2
 * Author: Miguel Barroso
 */

// Prevent direct access for security.
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue necessary styles.
function simple_booking_enqueue_scripts() {
    wp_enqueue_style('simple-booking-style', 'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css');
}
add_action('wp_enqueue_scripts', 'simple_booking_enqueue_scripts');

// Generate the booking form using a shortcode.
function simple_booking_form() {
    ob_start(); ?>
    <form id="simple-booking-form" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
        <?php wp_nonce_field('simple_booking_action', 'simple_booking_nonce'); ?>

        <!-- User input fields for booking -->
        <label for="name">Namn:</label>
        <input type="text" id="name" name="name" placeholder="Ditt namn" required>

        <label for="email">E-post:</label>
        <input type="email" id="email" name="email" placeholder="Din e-post" required>

        <label for="message">Meddelande:</label>
        <textarea id="message" name="message" placeholder="Skriv ett meddelande"></textarea>

        <!-- Date selection with cost calculation -->
        <label for="start-date">Startdatum:</label>
        <input type="date" id="start-date" name="start_date" required onchange="calculateNights()">

        <label for="end-date">Slutdatum:</label>
        <input type="date" id="end-date" name="end_date" required onchange="calculateNights()">

        <!-- Cost display section -->
        <p id="night-cost">Pris: 0 kr</p>
        <button type="submit" name="simple_booking_submit">Boka</button>
    </form>

    <script>
        // Live calculation of nights and cost
        function calculateNights() {
            const start = new Date(document.getElementById('start-date').value);
            const end = new Date(document.getElementById('end-date').value);
            if (!start.getTime() || !end.getTime()) {
                document.getElementById('night-cost').textContent = 'Pris: 0 kr';
                return;
            }
            const nights = Math.max(0, Math.floor((end - start) / (1000 * 60 * 60 * 24)));
            document.getElementById('night-cost').textContent = `Pris: ${nights * 299} kr (${nights} nätter)`;
        }
    </script>

    <?php
    // Show thank-you message if the form is submitted correctly
    if (isset($_POST['simple_booking_submit']) && wp_verify_nonce($_POST['simple_booking_nonce'], 'simple_booking_action')) {
        simple_booking_process();
        echo '<p style="color:green;">Tack för din bokning! Vi återkommer till dig inom kort.</p>';
    }
    return ob_get_clean();
}
add_shortcode('simple_booking', 'simple_booking_form');

// Process the booking form submission and send an email.
function simple_booking_process() {
    // Security check with nonce verification
    if (!wp_verify_nonce($_POST['simple_booking_nonce'], 'simple_booking_action')) {
        echo '<p style="color:red;">Ogiltig begäran.</p>';
        return;
    }

    // Collect and sanitize user inputs
    $page_title = get_the_title();
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $message_content = sanitize_textarea_field($_POST['message']);
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);

    // Calculate nights and total cost
    $nights = max(0, (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24));
    $total_price = $nights * 299;

    // Prepare and send email
    add_filter('wp_mail_content_type', function() { return 'text/plain'; });
    $mail_result = wp_mail(
        get_option('admin_email'),
        "Bokningsförfrågan från $page_title",
        "Namn: $name\nE-post: $email\nMeddelande: $message_content\nPeriod: $start_date till $end_date\nAntal nätter: $nights\nPris: $total_price kr"
    );
    remove_filter('wp_mail_content_type', function() { return 'text/plain'; });

    // Notify user if email sending fails
    if (!$mail_result) {
        echo '<p style="color:red;">E-post kunde inte skickas. Kontrollera e-postinställningarna.</p>';
    }
}
