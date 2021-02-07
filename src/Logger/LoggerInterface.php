<?php

declare(strict_types=1);

namespace Zkwbbr\WhoopsHelper\Logger;

interface LoggerInterface
{
    /**
     * Logs the message
     *
     * @param string $hash
     * @param string $message
     * @return void
     */
    public function log(string $hash, string $message): void;
    /**
     * Check if a log hash already exists
     *
     * @param string $hash
     * @return bool
     */
    public function logExist(string $hash): bool;
}