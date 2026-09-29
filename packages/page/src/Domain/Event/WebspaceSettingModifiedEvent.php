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

namespace Sulu\Page\Domain\Event;

use Sulu\Page\Domain\Model\WebspaceSettingInterface;

class WebspaceSettingModifiedEvent extends AbstractWebspaceSettingEvent
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(
        WebspaceSettingInterface $webspaceSetting,
        string $locale,
        private array $payload,
    ) {
        parent::__construct($webspaceSetting, $locale);
    }

    public function getEventType(): string
    {
        return 'modified';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }
}
