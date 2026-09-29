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

namespace Sulu\Page\Domain\Model;

use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Content\Domain\Model\ContentRichEntityTrait;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class WebspaceSetting implements WebspaceSettingInterface
{
    /**
     * @phpstan-use ContentRichEntityTrait<WebspaceSettingDimensionContentInterface>
     */
    use ContentRichEntityTrait;
    use AuditableTrait;

    public function __construct(protected string $webspaceKey)
    {
        $this->initializeDimensionContents();
    }

    public function getId(): string
    {
        return $this->webspaceKey;
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function getSecurityContext(): string
    {
        return \sprintf('sulu.webspaces.%s.webspace-settings', $this->webspaceKey);
    }

    /**
     * @return WebspaceSettingDimensionContentInterface
     */
    public function createDimensionContent(): DimensionContentInterface
    {
        return new WebspaceSettingDimensionContent($this);
    }
}
