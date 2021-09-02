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

namespace Sulu\Bundle\TrashBundle\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ListItemAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

final class TrashAdmin extends Admin
{
    public const SECURITY_CONTEXT = 'sulu.trash.trash';

    public const LIST_VIEW = 'sulu_trash.trash_items.list';

    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    /**
     * @var array<string, string>
     */
    private $restoreFormMapping;

    /**
     * @param array<string, string> $restoreFormMapping
     */
    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        LocalizationManagerInterface $localizationManager,
        array $restoreFormMapping
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->localizationManager = $localizationManager;
        $this->restoreFormMapping = $restoreFormMapping;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $trashItemsNavigationItem = new NavigationItem('sulu_trash.trash');
            $trashItemsNavigationItem->setPosition(100);
            $trashItemsNavigationItem->setView(static::LIST_VIEW);

            $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($trashItemsNavigationItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $locales = $this->localizationManager->getLocales();

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $toolbarActions = [];

            if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE)) {
                $toolbarActions[] = new ToolbarAction('sulu_admin.delete');
            }

            $listViewBuilder = $this->viewBuilderFactory->createListViewBuilder(static::LIST_VIEW, '/trash/:locale')
                ->setResourceKey(TrashItemInterface::RESOURCE_KEY)
                ->setListKey(TrashItemInterface::LIST_KEY)
                ->setTitle('sulu_trash.trash')
                ->addListAdapters(['table'])
                ->addLocales($locales)
                ->addToolbarActions($toolbarActions)
                ->addItemActions([
                    new ListItemAction('sulu_trash.restore'),
                ]);

            if (empty($toolbarActions)) {
                $listViewBuilder->disableSelection();
            }

            $viewCollection->add($listViewBuilder);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getSecurityContexts(): array
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Trash' => [
                    static::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_trash';
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [
            'restoreFormMapping' => $this->restoreFormMapping,
        ];
    }
}
