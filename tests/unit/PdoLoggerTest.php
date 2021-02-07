<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Zkwbbr\WhoopsHelper\Handler;
use Zkwbbr\WhoopsHelper\Logger\Pdo\PdoLogger;
use MetaRush\DataMapper\Builder;
use MetaRush\DataMapper\DataMapper;

class PdoLoggerTest extends TestCase
{
    private string $testDir = __DIR__ . '/testDir/';
    private string $dbFile;
    private \PDO $pdo;
    private PdoLogger $pdoLogger;
    private DataMapper $dataMapper;

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

        // ------------------------------------------------

        $this->dbFile = $this->testDir . 'test.db';

        $dsn = 'sqlite:' . $this->dbFile;

        // create test db if doesn't exist yet
        if (!\file_exists($this->dbFile)) {

            $this->pdo = new \PDO($dsn);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->pdo->query('
                CREATE TABLE test (
                `id`        INTEGER PRIMARY KEY AUTOINCREMENT,
                `dateTime`	TEXT,
                `hash`	    TEXT,
                `message`   TEXT
            )');
        }

        // ------------------------------------------------

        $this->dataMapper = (new Builder)
            ->setDsn($dsn)
            ->build();

        $this->pdoLogger = new PdoLogger($this->dataMapper, 'test', 'UTC');
    }

    public function tearDown(): void
    {
        // close the DB connections so unlink will work
        unset($this->dataMapper);
        unset($this->pdo);
        unset($this->pdoLogger);

        if (\file_exists($this->dbFile))
            \unlink($this->dbFile);
    }

    public function test_removeServerVars_pass()
    {
        $ex = new \Exception('test error');
        $handler = new Handler($ex, $this->pdoLogger, 'UTC');

        $sensitiveItems = [
            'ZWH_SMTP_PASS',
            'ZWH_PUSHOVER_USER_KEY',
        ];

        $handler->setItemsToRemoveFromServerVar($sensitiveItems);

        $handler->process();

        // ------------------------------------------------

        /* make sure values of $sensitiveItems does not exist in the log content */

        $rows = $this->dataMapper->findAll('test');

        $this->assertCount(1, $rows);

        $found = (false !== \strpos($rows[0]['message'], $sensitiveItems[0]));
        $this->assertEquals($found, false);

        $found = (false !== \strpos($rows[0]['message'], $sensitiveItems[1]));
        $this->assertEquals($found, false);
    }

    public function test_logMessageTwice_nothingHappens()
    {
        // seed logs with the hash of the test error in this method
        // warning: the exact line position of the "new \Exception" line in this method is related
        // to the hash that we'll seed, so don't move it, in case you do, you must edit the hash value
        $data = [
            'dateTime' => \date('Y-m-d H:i:s'),
            'hash'     => '450230634',
            'message'  => 'test'
        ];
        $this->dataMapper->create('test', $data);

        // ------------------------------------------------

        $ex = new \Exception('test error');
        $handler = new Handler($ex, $this->pdoLogger, 'UTC');
        $handler->process();

        $rows = $this->dataMapper->findAll('test');

        $this->assertCount(1, $rows);
    }

}