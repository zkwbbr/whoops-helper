<?php

declare(strict_types=1);

namespace Zkwbbr\WhoopsHelper;

use Zkwbbr\Utils;

class Handler
{
    private $ex;
    private $logDir; // must have trailing slash
    private $logTimeZone;
    private $errorHash;
    private $adjustedDateTime;
    private $events = [];
    private $nowDateTime;
    private $itemsToRemoveFromServerVar;

    const LOGGED_EVENT = 1;

    public function __construct(object $ex, string $logDir, string $logTimeZone)
    {
        $this->ex = $ex;
        $this->logDir = $logDir;
        $this->logTimeZone = $logTimeZone;
        $this->nowDateTime = \date('Y-m-d H:i:s', \time());
        $this->adjustedDateTime = Utils\AdjustedDateTimeByTimeZone::x($this->nowDateTime, $this->logTimeZone, 'Y-m-d H:i:s O');
    }

    /**
     * Process the error
     *
     * @return void
     */
    public function process(): void
    {
        $this->setErrorHash();

        if ($this->isNewError()) {

            $this->log($this->getErrorMessage());

            $this->addEvent(self::LOGGED_EVENT);
        }
    }

    /**
     * Add event for later listening (e.g., self::LOGGED_EVENT)
     *
     * @param int $event
     * @return void
     */
    private function addEvent(int $event): void
    {
        $this->events[] = $event;
    }

    /**
     * Invoke an action based on event
     *
     * @param int $event
     * @param \Zkwbbr\WhoopsHelper\Actions\ActionInterface $callback
     * @return void
     */
    public function invokeActionOnEvent(int $event, Actions\ActionInterface $action): void
    {
        foreach ($this->events as $v)
            if ($v == $event)
                $action->callback($this->ex, $this->getErrorHash(), $this->adjustedDateTime);
    }

    /**
     * Log error message
     *
     * @param string $message
     * @return void
     */
    private function log(string $message): void
    {
        $filename = Utils\AdjustedDateTimeByTimeZone::x($this->nowDateTime, $this->logTimeZone, 'Y-m-d_H-i-s_O') . '__' . $this->getErrorHash() . '.log';

        \file_put_contents($this->logDir . $filename, $message);
    }

    /**
     * Check if error message is already logged
     *
     * @return bool
     */
    private function isNewError(): bool
    {
        $errors = Utils\FilesFromDirectory::x($this->logDir, '~.*__' . $this->getErrorHash() . '\.log~');

        return $errors ? false : true;
    }

    /**
     * Get current error message
     *
     * @return string
     */
    private function getErrorMessage(): string
    {
        $msg = '[' . $this->adjustedDateTime . '] ' . $this->ex->getMessage() . ' on ' . $this->ex->getFile() . ' (' . $this->ex->getLine() . ')' . "\r\n\r\n" .
            '--------------------------------------------------' . "\r\n\r\n" .
            'Trace:' . "\r\n\r\n" .
            $this->ex->getTraceAsString() . "\r\n\r\n" .
            '--------------------------------------------------' . "\r\n\r\n" .
            '$_SERVER:' . "\r\n\r\n";

        $serverVars = $this->getSanitizedServerVar();

        foreach ($serverVars as $k => $v)
            if (!\is_array($v))
                $msg .= $k . ' = ' . $v . "\r\n";

        return $msg;
    }

    /**
     * Set error hash
     *
     * @return void
     */
    private function setErrorHash(): void
    {
        $this->errorHash = \crc32($this->ex->getMessage() . $this->ex->getFile() . $this->ex->getLine() . $this->ex->getTraceAsString());
    }

    /**
     * Get error hash
     *
     * @return int
     */
    public function getErrorHash(): int
    {
        return $this->errorHash;
    }

    /**
     * Set items to remove from $_SERVER var
     *
     * Use this if you don't want to log sensitive info that may be present in the $_SERVER var
     *
     * @param array $items
     * @return void
     */
    public function setItemsToRemoveFromServerVar(array $items): void
    {
        $this->itemsToRemoveFromServerVar = $items;
    }

    /**
     * Get sanitized server var
     *
     * Only useful if client used $this->setItemsToRemoveFromServerVar();
     *
     * @return array
     */
    private function getSanitizedServerVar(): array
    {
        $serverVars = $_SERVER;

        if (isset($this->itemsToRemoveFromServerVar) AND \count($this->itemsToRemoveFromServerVar) > 0)
            foreach ($this->itemsToRemoveFromServerVar as $item)
                unset($serverVars[$item]);

        return $serverVars;
    }

}