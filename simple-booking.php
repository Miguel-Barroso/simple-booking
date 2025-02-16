<?php
/**
 * Plugin Name: Simple Booking Plugin
 * Description: Version 1.6.9 - Now uses Cloudflare Turnstile for spam protection, referencing keys from wp-config.
 * Version: 1.6.9
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
        } elseif ( 'spam' === $response ) {
            $booking_response = '<p style="color:red;">Spam-kontroll misslyckades. Försök igen.</p>';
        }
    }

    // Pull the Turnstile site key from wp-config.php (if defined)
    $turnstile_site_key = defined( 'TURNSTILE_SITE_KEY' ) ? TURNSTILE_SITE_KEY : '';

    ob_start(); 
    ?>
    <!-- Turnstile script (must be loaded once per page) -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <!-- Inline CSS just for demo layout -->
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
            padding: 5px;  
            max-width: 100%; 
        }
        .short-date-field {
            width: 120px; 
        }
        .big-textarea {
            width: 250px;
            height: 100px;
        }
    </style>

    <form id="simple-booking-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="simple_booking">
        <?php wp_nonce_field( 'simple_booking_action', 'simple_booking_nonce' ); ?>
        <!-- Pass the current page slug so it can be used in the email subject -->
        <input type="hidden" name="page_slug" value="<?php echo esc_attr( get_post_field( 'post_name', get_post() ) ); ?>">

        <!-- Row 1: Start/End dates at top -->
        <div class="form-row">
            <label for="start-date">Startdatum:</label>
            <input 
                type="date" 
                id="start-date" 
                name="start_date" 
                class="short-date-field"
                required 
                placeholder="yyyy-mm-dd" 
                onchange="calculateNights()"
            >
            <label for="end-date" style="margin-left:20px;">Slutdatum:</label>
            <input 
                type="date" 
                id="end-date" 
                name="end_date" 
                class="short-date-field"
                required 
                placeholder="yyyy-mm-dd" 
                onchange="calculateNights()"
            >
        </div>

        <!-- Row 2: Price just underneath -->
        <div class="form-row">
            <p id="night-cost">Pris: 0 kr</p>
        </div>

        <!-- Row 3: Name label & field -->
        <div class="form-row">
            <label for="name">Namn:</label>
            <input type="text" id="name" name="name" placeholder="Ditt namn" required style="width:250px;">
        </div>

        <!-- Row 4: Email label & field -->
        <div class="form-row">
            <label for="email">E-post:</label>
            <input type="email" id="email" name="email" placeholder="Din e-post" required style="width:250px;">
        </div>

        <!-- Row 5: Message label -->
        <div class="form-row">
            <label for="message">Meddelande:</label>
        </div>

        <!-- Row 6: Larger textarea for message -->
        <div class="form-row">
            <textarea id="message" name="message" class="big-textarea" placeholder="Skriv ett meddelande"></textarea>
        </div>

        <!-- Row 7: Cloudflare Turnstile widget -->
        <div class="form-row">
            <div class="cf-challenge" 
                 data-sitekey="<?php echo esc_attr( $turnstile_site_key ); ?>" 
                 data-theme="light"
                 style="margin-bottom:10px;">
            </div>
        </div>

        <!-- Row 8: Submit button -->
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

    // 1. Retrieve Turnstile token from form submission
    $turnstile_token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( $_POST['cf-turnstile-response'] ) : '';
    if ( empty( $turnstile_token ) ) {
        wp_redirect( add_query_arg( 'booking_response', 'spam', wp_get_referer() ) );
        exit;
    }

    // 2. Get secret key from wp-config.php
    $secret_key = defined( 'TURNSTILE_SECRET_KEY' ) ? TURNSTILE_SECRET_KEY : '';

    // 3. Verify token with Cloudflare Turnstile
    $verify_url  = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $remote_ip   = $_SERVER['REMOTE_ADDR'];

    $response = wp_remote_post( $verify_url, array(
        'body' => array(
            'secret'   => $secret_key,
            'response' => $turnstile_token,
            'remoteip' => $remote_ip,
        ),
    ) );

    if ( is_wp_error( $response ) ) {
        // Could not contact Turnstile
        wp_redirect( add_query_arg( 'booking_response', 'spam', wp_get_referer() ) );
        exit;
    }

    $response_body = wp_remote_retrieve_body( $response );
    $result        = json_decode( $response_body, true );

    if ( empty( $result['success'] ) || $result['success'] !== true ) {
        // Turnstile says it's not a valid submission
        wp_redirect( add_query_arg( 'booking_response', 'spam', wp_get_referer() ) );
        exit;
    }

    // Turnstile verified successfully - proceed with booking
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
