<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Zkwbbr\WhoopsHelper;

class HandlerTest extends TestCase
{
    private $logDir = __DIR__ . '/testDir/';
    private $handler;

    public function setUp(): void
    {
        // ----------------------------------------------
        // load test smtp details from .env to $_ENV
        // ----------------------------------------------
        try {
            $dotenv = \Dotenv\Dotenv::create(__DIR__);
            $dotenv->load();
        } catch (\Dotenv\Exception\InvalidPathException $ex) {
            echo "\r\n" . $ex->getMessage() . "\r\n\r\n"
            . 'Instructions: Create a .env file inside tests/unit/ and use the '
            . 'content of sample.env as template';
        }

        $ex = new \Exception;
        $this->handler = new WhoopsHelper\Handler($ex, $this->logDir, 'UTC');
    }

    public function tearDown(): void
    {
        $logs = \Zkwbbr\Utils\FilesFromDirectory::x($this->logDir);

        foreach ($logs as $log)
            \unlink($this->logDir . $log);
    }

    public function testInvokeActionOnEvent()
    {
        $this->handler->process();

        // ----------------------------------------------
        // test pushover action
        // ----------------------------------------------

        $action = new WhoopsHelper\Actions\Pushover\Action(
            'example.com',
            $_ENV['ZWH_PUSHOVER_APP_KEY'],
            $_ENV['ZWH_PUSHOVER_USER_KEY']
        );

        $this->assertNull($this->handler->invokeActionOnEvent(WhoopsHelper\Handler::LOGGED_EVENT, $action));

        // ----------------------------------------------
        // test email action
        // ----------------------------------------------
        $smtpServers = [
            0 => (new \MetaRush\EmailFallback\Server)
                ->setHost($_ENV['ZWH_SMTP_HOST'])
                ->setUser($_ENV['ZWH_SMTP_USER'])
                ->setPass($_ENV['ZWH_SMTP_PASS'])
                ->setPort((int) $_ENV['ZWH_SMTP_PORT'])
                ->setEncr($_ENV['ZWH_SMTP_ENCR'])
        ];

        $mailBuilder = (new \MetaRush\EmailFallback\Builder)
            ->setServers($smtpServers)
            ->setAdminEmails([$_ENV['ZWH_ADMIN_EMAIL']])
            ->setNotificationFromEmail('noreply@example.com')
            ->setFromEmail('noreply@example.com')
            ->setAppName('ZkwbbrWhoopsHelper')
            ->setTos([$_ENV['ZWH_ADMIN_EMAIL']]);

        $action = new WhoopsHelper\Actions\Email\Action(
            'example.com',
            $mailBuilder
        );

        $this->assertNull($this->handler->invokeActionOnEvent(WhoopsHelper\Handler::LOGGED_EVENT, $action));
    }

    public function testRemoveServerVars()
    {
        $sensitiveItems = [
            'ZWH_SMTP_PASS',
            'ZWH_PUSHOVER_USER_KEY',
        ];

        $this->handler->setItemsToRemoveFromServerVar($sensitiveItems);

        $this->handler->process();

        // ------------------------------------------------
        // make sure values of $sensitiveItems does not exist in the log content

        $logFiles = \Zkwbbr\Utils\FilesFromDirectory::x($this->logDir);
        $logContent = \file_get_contents($this->logDir . $logFiles[0]);

        $counter = 0;
        foreach ($sensitiveItems as $item)
            if (false !== \strpos($logContent, $item))
                $counter++;

        $this->assertEquals(0, $counter);
    }

}