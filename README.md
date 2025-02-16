# Simple Booking Plugin

**Version:** 1.4  
**Author:** Miguel Barroso  
**Description:** A WordPress plugin for booking requests with name, email, message, and date fields, using `admin_post` for form submission and email notifications.

## 🚀 Features
- Collects Name, Email, Message, Start Date, and End Date.
- Supports logged-in and guest users.
- Sends email notifications to the site admin.
- Displays a confirmation page after submission.

## 📂 Installation
1. **Upload the Plugin:**
   - Place the `simple-booking.php` file in `wp-content/plugins/simple-booking/`.
2. **Activate the Plugin:**
   - Go to **WordPress Admin > Plugins** and activate **Simple Booking Plugin**.
3. **Use the Shortcode:**
   - Add `[simple_booking]` to any page or post.

## ⚙️ Usage
- The form submits to `admin-post.php` using `action=simple_booking`.
- `admin_post_simple_booking`: Processes submissions for logged-in users.
- `admin_post_nopriv_simple_booking`: Processes submissions for guests.
- On success, redirects users to `/tack-for-din-bokning/`.

## 🛡️ Security and Validation
- User inputs are sanitized using `sanitize_text_field()`, `sanitize_email()`, and `sanitize_textarea_field()`.
- Only `POST` requests are accepted.

## 💌 Email Handling
- Uses WordPress `wp_mail()` to send emails to the site admin.
- SMTP plugins (e.g., WP Mail SMTP) can override email behavior automatically.

## 🐛 Troubleshooting
- **404 Error on Submission:** Ensure permalinks are set correctly.
- **Email Not Sending:** Configure an SMTP plugin (e.g., WP Mail SMTP).
- **No Redirection:** Ensure `/tack-for-din-bokning/` page exists.

## 📌 Notes
- Compatible with SiteGround or any hosting that supports WordPress and SMTP.
- Customize the thank-you page URL by changing `wp_safe_redirect()`.

---
**Happy Booking! 🚀✨**
