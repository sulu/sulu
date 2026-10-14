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

use Sulu\Content\Domain\Model\AuditableInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;

/**
 * @extends DimensionContentInterface<WebspaceSettingInterface>
 */
interface WebspaceSettingDimensionContentInterface extends DimensionContentInterface, TemplateInterface, WorkflowInterface, AuditableInterface
{
}
