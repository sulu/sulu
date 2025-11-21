<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Application\Message;

class CopyLocaleArticleMessage
{
    /**
     * @var array{
     *     uuid?: string
     * }
     */
    private array $identifier;

    private string $sourceLocale;

    private string $targetLocale;

    /**
     * @param array{
     *     uuid?: string
     * } $identifier
     */
    public function __construct(array $identifier, string $sourceLocale, string $targetLocale)
    {
        $this->identifier = $identifier;
        $this->sourceLocale = $sourceLocale;
        $this->targetLocale = $targetLocale;
    }

    /**
     * @return array{
     *     uuid?: string
     * }
     */
    public function getIdentifier(): array
    {
        return $this->identifier;
    }

    public function getSourceLocale(): string
    {
        return $this->sourceLocale;
    }

    public function getTargetLocale(): string
    {
        return $this->targetLocale;
    }
}
