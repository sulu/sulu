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

use Sulu\Content\Domain\Model\DimensionContentInterface;

class RestoreWebspaceSettingVersionMessage
{
    public function __construct(
        private string $webspaceKey,
        private int $version,
        private string $locale,
        private string $stage = DimensionContentInterface::STAGE_DRAFT,
    ) {
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getStage(): string
    {
        return $this->stage;
    }
}
