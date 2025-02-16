<?php
/**
 * Plugin Name: Simple Booking Plugin
 * Description: Version 1.6.7 - Updated layout with placeholders for date fields (yyyy-mm-dd).
 * Version: 1.6.7
 * Author: Miguel Barroso
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Shortcode function to render the form
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

    ob_start(); 
    ?>
    <!-- Inline CSS to demonstrate layout changes -->
    <style>
        #simple-booking-form .form-row {
            margin-bottom: 10px;
        }
        #simple-booking-form label {
            display: inline-block;
            width: 100px; /* Adjust label width as needed */
            margin-right: 5px;
        }
        #simple-booking-form input,
        #simple-booking-form textarea {
            padding: 5px;  /* The requested padding */
            width: 250px;  /* Adjust input/textarea width as desired */
            max-width: 100%; /* Ensure it stays responsive */
        }
    </style>

    <form id="simple-booking-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="simple_booking">
        <?php wp_nonce_field( 'simple_booking_action', 'simple_booking_nonce' ); ?>
        <!-- Pass the current page slug so it can be used in the email subject -->
        <input type="hidden" name="page_slug" value="<?php echo esc_attr( get_post_field( 'post_name', get_post() ) ); ?>">

        <!-- Row 1: Name label & field on same line -->
        <div class="form-row">
            <label for="name">Namn:</label>
            <input type="text" id="name" name="name" placeholder="Ditt namn" required>
        </div>

        <!-- Row 2: Email label & field on same line -->
        <div class="form-row">
            <label for="email">E-post:</label>
            <input type="email" id="email" name="email" placeholder="Din e-post" required>
        </div>

        <!-- Row 3: Message label alone -->
        <div class="form-row">
            <label for="message">Meddelande:</label>
        </div>

        <!-- Row 4: Message textarea -->
        <div class="form-row">
            <textarea id="message" name="message" placeholder="Skriv ett meddelande"></textarea>
        </div>

        <!-- Row 5: Dates on one line (start & end) with placeholders -->
        <div class="form-row">
            <label for="start-date">Startdatum:</label>
            <input 
                type="date" 
                id="start-date" 
                name="start_date" 
                required 
                placeholder="yyyy-mm-dd" 
                onchange="calculateNights()"
            >

            <label for="end-date" style="margin-left:20px;">Slutdatum:</label>
            <input 
                type="date" 
                id="end-date" 
                name="end_date" 
                required 
                placeholder="yyyy-mm-dd" 
                onchange="calculateNights()"
            >
        </div>

        <!-- Row 6: Price display -->
        <div class="form-row">
            <p id="night-cost">Pris: 0 kr</p>
        </div>

        <!-- Row 7: Submit button -->
        <div class="form-row">
            <button type="submit">Boka</button>
        </div>
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

// Named function to set the email content type to plain text
function set_plain_text_mail_content_type() {
    return 'text/plain';
}

// Process booking and redirect back with a response
function simple_booking_process() {
    if ( ! isset( $_POST['simple_booking_nonce'] ) || ! wp_verify_nonce( $_POST['simple_booking_nonce'], 'simple_booking_action' ) ) {
        wp_redirect( add_query_arg( 'booking_response', 'failure', wp_get_referer() ) );
        exit;
    }

    $page_slug       = isset( $_POST['page_slug'] ) ? sanitize_text_field( $_POST['page_slug'] ) : 'n/a';
    $name            = sanitize_text_field( $_POST['name'] );
    $email           = sanitize_email( $_POST['email'] );
    $message_content = sanitize_textarea_field( $_POST['message'] );
    $start_date      = sanitize_text_field( $_POST['start_date'] );
    $end_date        = sanitize_text_field( $_POST['end_date'] );

    $nights      = (int) max( 0, ( strtotime( $end_date ) - strtotime( $start_date ) ) / 86400 );
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
