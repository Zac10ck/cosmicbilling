<?php
/**
 * Email Configuration
 * COSMIC SURGICALS - Invoice Email Notifications
 *
 * Using Gmail SMTP with App Password
 */

// Gmail SMTP Settings
$emailConfig = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',  // Use TLS encryption
    'smtp_auth' => true,

    // Sender credentials (Gmail with App Password)
    'smtp_username' => 'cosmicsurgicaldev@gmail.com',
    'smtp_password' => 'ehejrfetouuvdinj',  // App Password (no spaces)

    // Email addresses
    'from_email' => 'cosmicsurgicaldev@gmail.com',
    'from_name' => 'COSMIC SURGICALS',

    // Notification recipient (boss gets invoice notifications)
    'notify_email' => 'cosmicsurgical@gmail.com',
    'notify_name' => 'COSMIC SURGICALS Admin',

    // Email settings
    'send_notifications' => true,  // Set to false to disable email notifications
    'debug_mode' => 0,  // 0 = off, 1 = client messages, 2 = client and server messages
];

/**
 * Get email configuration
 */
function getEmailConfig() {
    global $emailConfig;
    return $emailConfig;
}
