<?php

declare(strict_types=1);

namespace Zkwbbr\WhoopsHelper\Logger\Pdo;

use Zkwbbr\WhoopsHelper\Logger\LoggerInterface;
use Zkwbbr\Utils\AdjustedDateTimeByTimeZone;
use MetaRush\DataMapper\DataMapper;

class PdoLogger implements LoggerInterface
{
    private DataMapper $dataMapper;
    private string $table;
    private string $timeZone;

    public function __construct(DataMapper $dataMapper, string $table, string $timeZone)
    {
        $this->dataMapper = $dataMapper;
        $this->table = $table;
        $this->timeZone = $timeZone;
    }

    public function log(string $hash, string $message): void
    {
        $data = [
            'createdOn' => AdjustedDateTimeByTimeZone::x('now', $this->timeZone, 'Y-m-d H:i:s'),
            'hash'     => $hash,
            'message'  => $message,
        ];

        $this->dataMapper->create($this->table, $data);
    }

    public function logExist(string $hash): bool
    {
        $where = ['hash' => $hash];
        $row = $this->dataMapper->findOne($this->table, $where);

        return (bool) $row;
    }

}