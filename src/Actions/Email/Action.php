<?php

declare(strict_types=1);

namespace Zkwbbr\WhoopsHelper\Actions\Email;

/**
 * Send error to email
 */
class Action implements \Zkwbbr\WhoopsHelper\Actions\ActionInterface
{
    private $siteUrl;
    private $mailBuilder;

    public function __construct(string $siteUrl, \MetaRush\EmailFallback\Builder $mailBuilder)
    {
        $this->siteUrl = $siteUrl;
        $this->mailBuilder = $mailBuilder;
    }

    public function callback($ex, $hash, $date): void
    {
        $this->mailBuilder->setSubject('Error alert on ' . $this->siteUrl . ' #' . $hash);
        $this->mailBuilder->setBody('[' . $date . '] ' . $ex->getMessage() . ' on ' . $ex->getFile() . ' (' . $ex->getLine() . ')');

        $mailer = $this->mailBuilder->build();

        $mailer->sendEmailFallback();
    }
}
