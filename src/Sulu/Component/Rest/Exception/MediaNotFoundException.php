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

namespace Sulu\Component\Rest\Exception;

class MediaNotFoundException extends \Exception implements MediaNotFoundExceptionInterface
{
    /**
     * @param array{id: int|string, resourceKey: string} $resource
     */
    public function __construct(
        private array $resource,
    ) {
        parent::__construct(
            \sprintf(
                'Media with the id "%s" not found.',
                $resource['id'],
            ),
            static::EXCEPTION_CODE_MEDIA_NOT_FOUND
        );
    }

    public function getResource(): array
    {
        return $this->resource;
    }
}
