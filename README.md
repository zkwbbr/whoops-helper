# zkwbbr/whoops-helper

On production mode, log an error message only once.
Optionally, notify admins via email or Pushover.
On development mode, show Whoops Pretty Page.

## Install

Install via composer as `zkwbbr/whoops-helper`

## Sample Usage

Put the ff. code on top of your script.

```php
<?php

error_reporting(E_ALL);

use Zkwbbr\WhoopsHelper;

define('APP_DEV_MODE', true); // set to false in production
define('APP_ADMIN_EMAIL', 'admin@example.com');
define('APP_LOG_DIR', '/path/to/logs/');
define('APP_URL', 'example.com');
define('APP_SMTP_HOST', 'example.com');
define('APP_SMTP_USER', 'user');
define('APP_SMTP_PASS', 'pass');
define('APP_SMTP_PORT', '25');
define('APP_SMTP_ENCR', 'TLS');
define('APP_PUSHOVER_APP_KEY', 'example');
define('APP_PUSHOVER_USER_KEY', 'example');

$whoops = new \Whoops\Run;

if (APP_DEV_MODE) {

    ini_set('display_errors', '1');

    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);

} else {

    ini_set('display_errors', '0');

    $whoops->pushHandler(function ($ex) {

        $handler = new WhoopsHelper\Handler($ex, APP_LOG_DIR, 'UTC');

        // ----------------------------------------------
        // optionally send email on first instance of an error
        // ----------------------------------------------
        $smtpServers = [
            0 => (new \MetaRush\EmailFallback\Server)
                ->setHost(APP_SMTP_HOST)
                ->setUser(APP_SMTP_USER)
                ->setPass(APP_SMTP_PASS)
                ->setPort(APP_SMTP_PORT)
                ->setEncr(APP_SMTP_ENCR)
        ];

        $mailBuilder = (new \MetaRush\EmailFallback\Builder)
            ->setServers($smtpServers)
            ->setAdminEmails([APP_ADMIN_EMAIL])
            ->setNotificationFromEmail('noreply@example.com')
            ->setFromEmail('noreply@example.com')
            ->setAppName(APP_URL)
            ->setTos([APP_ADMIN_EMAIL]);

        $action = new WhoopsHelper\Actions\Email\Action(APP_URL, $mailBuilder);

        $handler->invokeActionOnEvent(
            WhoopsHelper\Handler::LOGGED_EVENT,
            $action
        );

        // ----------------------------------------------
        // optionally send Pushover notification on first instance of an error
        // ----------------------------------------------

        $action = new WhoopsHelper\Actions\Pushover\Action(
            APP_URL, APP_PUSHOVER_APP_KEY, APP_PUSHOVER_USER_KEY
        );

        $handler->invokeActionOnEvent(
            WhoopsHelper\Handler::LOGGED_EVENT,
            $action
        );

        // ----------------------------------------------
        // send response to user/client
        // ----------------------------------------------

        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        header('Status: 500 Internal Server Error');

        echo 'Sorry an error occurred, our admins have been notified';
    });
}

$whoops->register();
```

Note: Using email and Pushover notifications are optional. Just remove them from the sample code above.