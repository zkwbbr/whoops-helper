<?php

declare(strict_types=1);

namespace Zkwbbr\WhoopsHelper\Actions\Email;

use Zkwbbr\WhoopsHelper\Actions\ActionInterface;
use MetaRush\EmailFallback\Builder;

/**
 * Send error to email
 */
class Action implements ActionInterface
{
    private $siteUrl;
    private $mailBuilder;

    public function __construct(string $siteUrl, Builder $mailBuilder)
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