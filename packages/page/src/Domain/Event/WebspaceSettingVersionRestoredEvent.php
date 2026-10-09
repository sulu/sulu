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

class WebspaceSettingVersionRestoredEvent extends AbstractWebspaceSettingEvent
{
    public function __construct(
        WebspaceSettingInterface $webspaceSetting,
        string $locale,
        private int $version,
    ) {
        parent::__construct($webspaceSetting, $locale);
    }

    public function getEventType(): string
    {
        return 'version_restored';
    }

    public function getEventContext(): array
    {
        return [
            'version' => $this->version,
        ];
    }
}
