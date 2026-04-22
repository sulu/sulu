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

use Sulu\Content\Domain\Model\PublicationRequest\PublicationRequest;

class PublicationRequestClosedException extends \RuntimeException
{
    public function __construct(PublicationRequest $publicationRequest)
    {
        parent::__construct(\sprintf(
            'Publication request "%s" cannot accept decisions in status "%s".',
            $publicationRequest->getId(),
            $publicationRequest->getStatus()->value,
        ));
    }
}
