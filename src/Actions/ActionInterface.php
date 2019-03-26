<?php

declare(strict_types=1);

namespace Zkwbbr\WhoopsHelper\Actions;

interface ActionInterface
{
    public function callback(\Whoops\Exception\ErrorException $ex, string $hash, string $date): void;
}
