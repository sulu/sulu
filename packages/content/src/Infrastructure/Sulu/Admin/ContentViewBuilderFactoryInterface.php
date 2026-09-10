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

namespace Sulu\Content\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

interface ContentViewBuilderFactoryInterface
{
    /**
     * @template T of DimensionContentInterface
     *
     * @param class-string<ContentRichEntityInterface<T>> $contentRichEntityClass
     *
     * @return array<string, ToolbarAction>
     */
    public function getDefaultToolbarActions(
        string $contentRichEntityClass
    ): array;

    /**
     * The `save` and `approval` dropdowns, merged over the default toolbar actions. Pass permission
     * conditions when plain `_permissions` does not apply, as for webspace-scoped pages.
     *
     * @return array{save: DropdownToolbarAction, approval: ToolbarAction}
     */
    public function getWorkflowTransitionRequestToolbarActions(
        string $resourceKey,
        string $saveVisibleCondition = '(!_permissions || _permissions.edit)',
        string $publishVisibleCondition = '(!_permissions || _permissions.live)',
        string $reviewVisibleCondition = '(!_permissions || _permissions.review || _permissions.edit || _permissions.live)',
    ): array;

    /**
     * @template T of DimensionContentInterface
     *
     * @param class-string<ContentRichEntityInterface<T>> $contentRichEntityClass
     * @param array<string, ToolbarAction> $toolbarActions
     *
     * @return ViewBuilderInterface[]
     */
    public function createViews(
        string $contentRichEntityClass,
        string $editParentView,
        ?string $addParentView = null,
        ?string $securityContext = null,
        ?array $toolbarActions = null
    ): array;
}
