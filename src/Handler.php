<?php

declare(strict_types=1);

namespace Zkwbbr\WhoopsHelper;

use Zkwbbr\WhoopsHelper\Logger\LoggerInterface;
use Zkwbbr\WhoopsHelper\Actions\ActionInterface;
use Zkwbbr\Utils\AdjustedDateTimeByTimeZone;

class Handler
{
    public const LOGGED_EVENT = 1;

    private \Exception $ex;
    private LoggerInterface $logger;
    private string $logTimeZone;
    private string $adjustedDateTime;
    private array $events = [];
    private array $itemsToRemoveFromServerVar;

    public function __construct(
        \Exception $ex,
        LoggerInterface $logger,
        string $logTimeZone)
    {
        $this->ex = $ex;
        $this->logger = $logger;
        $this->logTimeZone = $logTimeZone;
        $this->adjustedDateTime = AdjustedDateTimeByTimeZone::x('now', $this->logTimeZone, 'Y-m-d H:i:s O');
    }

    /**
     * Process the log
     *
     * @return void
     */
    public function process(): void
    {
        if (!$this->logger->logExist($this->getMessageHash())) {

            $this->logger->log($this->getMessageHash(), $this->getMessage());

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
     * @param ActionInterface $action
     * @return void
     */
    public function invokeActionOnEvent(int $event, ActionInterface $action): void
    {
        foreach ($this->events as $v)
            if ($v == $event)
                $action->callback($this->ex, $this->getMessageHash(), $this->adjustedDateTime);
    }

    /**
     * Get current message
     *
     * @return string
     */
    private function getMessage(): string
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
     * Get message hash
     *
     * @return int
     */
    public function getMessageHash(): string
    {
        return (string) \crc32($this->ex->getMessage() . $this->ex->getFile() . $this->ex->getLine() . $this->ex->getTraceAsString());
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