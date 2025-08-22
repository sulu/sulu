<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Application\Message;

class RefreshReferenceMessage
{
    public function __construct(
        private string $referenceResourceKey,
        private string $resourceId,
        private string $locale,
        private string $stage,
    ) {
    }

    public function getReferenceResourceKey(): string
    {
        return $this->referenceResourceKey;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    /**
     * @return array{
     *     resourceId: string,
     *     resourceKey: string,
     *     locale: string,
     *     stage: string
     * }
     */
    public function getFilter(): array
    {
        return [
            'resourceId' => $this->resourceId,
            'resourceKey' => $this->referenceResourceKey,
            'locale' => $this->locale,
            'stage' => $this->stage,
        ];
    }
}
