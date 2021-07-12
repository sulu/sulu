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

namespace Sulu\Bundle\TrashBundle\Domain\Exception;

class TrashItemTranslationNotFoundException extends \Exception
{
    public function __construct(?string $locale)
    {
        parent::__construct(
            \sprintf('Translation for locale "%s" not found.', $locale)
        );
    }
}
