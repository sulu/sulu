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

namespace Sulu\Article\Infrastructure\Sulu\Trash;

/**
 * @internal
 */
final class ArticleRestoreResult
{
    public function __construct(
        private string $id,
        private string $group,
        private string $locale,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
