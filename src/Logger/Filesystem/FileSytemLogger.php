<?php

declare(strict_types=1);

namespace Zkwbbr\WhoopsHelper\Logger\FileSystem;

use Zkwbbr\WhoopsHelper\Logger\LoggerInterface;
use Zkwbbr\Utils\FilesFromDirectory;
use Zkwbbr\Utils\AdjustedDateTimeByTimeZone;

class FileSytemLogger implements LoggerInterface
{
    private string $logDir;
    private string $timeZone;

    public function __construct(string $logDir, string $timeZone)
    {
        $this->logDir = $logDir;
        $this->timeZone = $timeZone;
    }

    public function log(string $hash, string $message): void
    {
        $filename = AdjustedDateTimeByTimeZone::x('now', $this->timeZone, 'Y-m-d_H-i-s_O') . '__' . $hash . '.log';

        \file_put_contents($this->logDir . $filename, $message);
    }

    public function logExist(string $hash): bool
    {
        $logs = FilesFromDirectory::x($this->logDir, '~.*__' . $hash . '\.log~');

        return (bool) $logs;
    }

}