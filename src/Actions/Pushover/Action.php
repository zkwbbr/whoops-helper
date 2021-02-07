<?php

declare(strict_types=1);

namespace Zkwbbr\WhoopsHelper\Actions\Pushover;

use Zkwbbr\WhoopsHelper\Actions\ActionInterface;

/**
 * Send error to a Pushover account
 */
class Action implements ActionInterface
{
    private $siteUrl;
    private $pushoverAppKey;
    private $pushoverUserKey;

    public function __construct(string $siteUrl, string $pushoverAppKey, string $pushoverUserKey)
    {
        $this->siteUrl = $siteUrl;
        $this->pushoverAppKey = $pushoverAppKey;
        $this->pushoverUserKey = $pushoverUserKey;
    }

    public function callback($ex, $hash, $date): void
    {
        $subject = 'Error alert on ' . $this->siteUrl . ' #' . $hash;
        $body = $ex->getMessage() . ' on ' . $ex->getFile() . ' (' . $ex->getLine() . ')';

        $push = new \Pushover();
        $push->setToken($this->pushoverAppKey);
        $push->setUser($this->pushoverUserKey);
        $push->setTitle($subject);
        $push->setMessage($body);

        if (!$push->send())
            throw new \Error('Pushover notification appears to be not working');
    }

}