<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\Message;

class ApplyWorkflowTransitionPageMessage
{
    /**
     * @var array{
     *     uuid?: string,
     * }
     */
    private array $identifier;

    private string $locale;

    private string $transitionName;

    /**
     * @param array{
     *     uuid?: string
     * } $identifier
     */
    public function __construct(array $identifier, string $locale, string $transitionName)
    {
        $this->identifier = $identifier;
        $this->locale = $locale;
        $this->transitionName = $transitionName;
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

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getTransitionName(): string
    {
        return $this->transitionName;
    }
}
