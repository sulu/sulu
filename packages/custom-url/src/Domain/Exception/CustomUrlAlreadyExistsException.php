<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Domain\Exception;

class CustomUrlAlreadyExistsException extends \Exception
{
    public function __construct(
        private readonly string $title,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(\sprintf('A custom URL with title "%s" already exists', $title), $code, $previous);
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
