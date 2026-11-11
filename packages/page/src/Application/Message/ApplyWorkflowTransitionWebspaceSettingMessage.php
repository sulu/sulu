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

namespace Sulu\Page\Application\Message;

class ApplyWorkflowTransitionWebspaceSettingMessage
{
    public function __construct(
        private string $webspaceKey,
        private string $locale,
        private string $transitionName,
    ) {
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
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
