<?php
// Local config overrides for Driver module
    // Copy of the admin module's email settings so OTP emails work out of the box.
    // SECURITY: This file may contain secrets; add includes/env.local.php to .gitignore.

    if (!defined('MAIL_FROM'))      define('MAIL_FROM', 'logistics2jetlougetravels@gmail.com');
    if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'Jetlouge Travels');

    if (!defined('SMTP_HOST'))      define('SMTP_HOST', 'smtp.gmail.com');
    if (!defined('SMTP_USERNAME'))  define('SMTP_USERNAME', 'logistics2jetlougetravels@gmail.com');
    // Google app passwords are 16 chars WITHOUT spaces
    if (!defined('SMTP_PASSWORD'))  define('SMTP_PASSWORD', 'kkyvjyhjylomvdlw');
    if (!defined('SMTP_PORT'))      define('SMTP_PORT', 587);
    if (!defined('SMTP_SECURE'))    define('SMTP_SECURE', 'tls');

// if (!defined('SENDGRID_API_KEY')) define('SENDGRID_API_KEY', 'SG.xxxxxx');

if (!defined('MAIL_DEBUG'))     define('MAIL_DEBUG', false);
