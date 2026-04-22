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

namespace Sulu\Content\Domain\Exception;

class DuplicateActivePublicationRequestException extends \RuntimeException
{
    public function __construct(string $resourceKey, string $resourceId, string $locale)
    {
        parent::__construct(\sprintf(
            'An active publication request already exists for "%s" "%s" in locale "%s".',
            $resourceKey,
            $resourceId,
            $locale,
        ));
    }
}
