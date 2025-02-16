<?php
/**
 * Plugin Name: Simple Booking Plugin
 * Description: Version 1.6.4 - Display messages below form and fix page title in email subject.
 * Version: 1.6.4
 * Author: Miguel Barroso
 */

if (!defined('ABSPATH')) {
    exit;
}

// Render form with AJAX-based result message below the submit button
function simple_booking_form() {
    ob_start(); ?>
    <form id="simple-booking-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="simple_booking">
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
        
        <div id="booking-response"></div>
    </form>

    <script>
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
    return ob_get_clean();
}
add_shortcode('simple_booking', 'simple_booking_form');

// Process the booking form and display messages under the form
function simple_booking_process() {
    $response_message = '';

    if (!isset($_POST['simple_booking_nonce']) || !wp_verify_nonce($_POST['simple_booking_nonce'], 'simple_booking_action')) {
        $response_message = '<p style="color:red;">Ogiltig begäran: Nonce misslyckades.</p>';
    } else {
        $page_title = wp_get_document_title();
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $message_content = sanitize_textarea_field($_POST['message']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        $nights = max(0, (strtotime($end_date) - strtotime($start_date)) / 86400);
        $total_price = $nights * 299;

        add_filter('wp_mail_content_type', fn() => 'text/plain');
        $mail_result = wp_mail(
            get_option('admin_email'),
            "Bokningsförfrågan från $page_title",
            "Namn: $name\nE-post: $email\nMeddelande: $message_content\nPeriod: $start_date till $end_date\nAntal nätter: $nights\nPris: $total_price kr"
        );
        remove_filter('wp_mail_content_type', fn() => 'text/plain');

        if ($mail_result) {
            $response_message = '<p style="color:green;">Tack för din bokning! Vi återkommer inom kort.</p>';
        } else {
            $response_message = '<p style="color:red;">E-postmisslyckande: Kontrollera SMTP eller wp_mail-konfigurationen.</p>';
        }
    }

    echo '<div id="booking-response">' . $response_message . '</div>';
    exit;
}

add_action('admin_post_simple_booking', 'simple_booking_process');
add_action('admin_post_nopriv_simple_booking', 'simple_booking_process');
