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
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentTrait;
use Sulu\Content\Domain\Model\TemplateTrait;
use Sulu\Content\Domain\Model\WorkflowTrait;

class WebspaceSettingDimensionContent implements WebspaceSettingDimensionContentInterface
{
    use DimensionContentTrait;
    use TemplateTrait;
    use WorkflowTrait;
    use AuditableTrait;

    protected int $id;

    public function __construct(protected WebspaceSettingInterface $webspaceSetting)
    {
        $this->created = new \DateTimeImmutable();
        $this->changed = new \DateTimeImmutable();
    }

    /**
     * @return WebspaceSettingInterface
     */
    public function getResource(): ContentRichEntityInterface
    {
        return $this->webspaceSetting;
    }

    public static function getTemplateType(): string
    {
        return WebspaceSettingInterface::TEMPLATE_TYPE;
    }

    public static function getResourceKey(): string
    {
        return WebspaceSettingInterface::RESOURCE_KEY;
    }
}
