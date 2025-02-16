<?php
/**
 * Plugin Name: Simple Booking Plugin
 * Description: Version 1.6.6 - Shows messages below the form and captures page slug in email subject.
 * Version: 1.6.6
 * Author: Miguel Barroso
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Render form with messages displayed below the button
function simple_booking_form() {
    // Check if a response message exists via a query parameter
    $booking_response = '';
    if ( isset( $_GET['booking_response'] ) ) {
        $response = sanitize_text_field( $_GET['booking_response'] );
        if ( 'success' === $response ) {
            $booking_response = '<p style="color:green;">Tack för din bokning! Vi återkommer inom kort.</p>';
        } elseif ( 'failure' === $response ) {
            $booking_response = '<p style="color:red;">E-postmisslyckande: Kontrollera SMTP eller wp_mail-konfigurationen.</p>';
        }
    }
    ob_start(); ?>
    <form id="simple-booking-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="simple_booking">
        <?php wp_nonce_field( 'simple_booking_action', 'simple_booking_nonce' ); ?>
        <!-- Pass the current page slug so it can be used in the email subject -->
        <input type="hidden" name="page_slug" value="<?php echo esc_attr( get_post_field( 'post_name', get_post() ) ); ?>">
        
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
    <div id="booking-response"><?php echo $booking_response; ?></div>

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
add_shortcode( 'simple_booking', 'simple_booking_form' );

// Named function to set the email content type to plain text.
function set_plain_text_mail_content_type() {
    return 'text/plain';
}

// Process booking and redirect back with a response
function simple_booking_process() {
    if ( ! isset( $_POST['simple_booking_nonce'] ) || ! wp_verify_nonce( $_POST['simple_booking_nonce'], 'simple_booking_action' ) ) {
        wp_redirect( add_query_arg( 'booking_response', 'failure', wp_get_referer() ) );
        exit;
    }

    // Use the page slug passed via the hidden field
    $page_slug = isset( $_POST['page_slug'] ) ? sanitize_text_field( $_POST['page_slug'] ) : 'n/a';
    
    $name           = sanitize_text_field( $_POST['name'] );
    $email          = sanitize_email( $_POST['email'] );
    $message_content= sanitize_textarea_field( $_POST['message'] );
    $start_date     = sanitize_text_field( $_POST['start_date'] );
    $end_date       = sanitize_text_field( $_POST['end_date'] );

    // Calculate nights (casting to integer to avoid decimals)
    $nights = (int) max( 0, ( strtotime( $end_date ) - strtotime( $start_date ) ) / 86400 );
    $total_price = $nights * 299;

    // Set the email to plain text
    add_filter( 'wp_mail_content_type', 'set_plain_text_mail_content_type' );
    $mail_result = wp_mail(
        get_option( 'admin_email' ),
        "Bokningsförfrågan från sida: $page_slug",
        "Namn: $name\nE-post: $email\nMeddelande: $message_content\nPeriod: $start_date till $end_date\nAntal nätter: $nights\nPris: $total_price kr"
    );
    remove_filter( 'wp_mail_content_type', 'set_plain_text_mail_content_type' );

    $response = $mail_result ? 'success' : 'failure';
    wp_redirect( add_query_arg( 'booking_response', $response, wp_get_referer() ) );
    exit;
}

add_action( 'admin_post_simple_booking', 'simple_booking_process' );
add_action( 'admin_post_nopriv_simple_booking', 'simple_booking_process' );
