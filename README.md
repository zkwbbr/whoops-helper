# zkwbbr/whoops-helper

On production mode, log an error message only once.
Optionally, notify admins via email or Pushover.
On development mode, show Whoops Pretty Page.

## Install

Install via composer as `zkwbbr/whoops-helper`

## Sample Usage

### Using FileSystem logger

Put the ff. code on top of your script.

```php
<?php

\error_reporting(E_ALL);

use Zkwbbr\WhoopsHelper;

// the following constants are arbitrary and not required
define('APP_DEV_MODE', true); // set to false in production
define('APP_ADMIN_EMAIL', 'admin@example.com');
define('APP_LOG_DIR', '/path/to/logs/');
define('APP_LOG_TIME_ZONE', 'UTC');
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

    \ini_set('display_errors', '1');

    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);

} else {

    ini_set('display_errors', '0');

    $whoops->pushHandler(function ($ex) {

        $logger = new WhoopsHelper\Logger\Filesystem\FileSytemLogger(APP_LOG_DIR, APP_LOG_TIME_ZONE);

        $handler = new WhoopsHelper\Handler($ex, $logger, APP_LOG_TIME_ZONE);

        // optionally remove sensitive info from $_SERVER var in the log
        $sampleSensitiveInfo = ['PHP_AUTH_PW'];
        $handler->setItemsToRemoveFromServerVar($sampleSensitiveInfo);

        $handler->process();

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

        \header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        \header('Status: 500 Internal Server Error');

        echo 'Sorry an error occurred, our admins have been notified';
    });
}

$whoops->register();
```

Note: Using email and Pushover notifications are optional.
You can just remove them from the sample code above if you don't want to use them.

### Using PDO logger

You can use any PDO database (e.g., MySQL, PostgreSQL, SQLite)

Create a table with the ff. columns:

- `createdOn` DATETIME
- `hash` VARCHAR (10)
- `message` TEXT (length depends on how big your log message is)

Make `hash` column UNIQUE if you want

Replace the $logger adapter (line 52 from the above sample) with the ff.

```php

$dataMapper = (new \MetaRush\DataMapper\DataMapper)
    ->setDsn('mysql:host=locolhost;dbname=you_db_name')
    ->setDbUser('your_db_user')
    ->setDbPass('your_db_pass')
    ->build();

$logger = new WhoopsHelper\Logger\Pdo\PdoLogger($dataMapper, 'your_db_table', APP_LOG_TIME_ZONE);
```