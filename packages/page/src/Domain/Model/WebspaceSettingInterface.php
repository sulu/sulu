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

use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;

/**
 * @extends ContentRichEntityInterface<WebspaceSettingDimensionContentInterface>
 */
interface WebspaceSettingInterface extends AuditableInterface, ContentRichEntityInterface, SecuredEntityInterface
{
    public const TEMPLATE_TYPE = 'webspace_settings';
    public const RESOURCE_KEY = 'webspace_settings';

    /**
     * The identifier of a webspace setting is the key of its webspace, there is exactly one per webspace.
     */
    public function getId(): string;

    public function getWebspaceKey(): string;
}
