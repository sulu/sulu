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

namespace Sulu\Page\Infrastructure\Sulu\Admin;

use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\FormViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ListItemAction;
use Sulu\Bundle\AdminBundle\Admin\View\PreviewFormViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;

/**
 * Adds the "Settings" tab to the webspaces which define a `<webspace-settings-form>`, it shows the form directly
 * with the tabs "Content", "Versions" and "Activity".
 *
 * @internal no backwards compatibility promise is given for this class it could be removed or changed at any time
 */
class WebspaceSettingAdmin extends Admin
{
    public const TABS_VIEW = 'sulu_page.webspace_settings';

    public const CONTENT_VIEW = self::TABS_VIEW . '.content';

    public const VERSIONS_VIEW = self::TABS_VIEW . '.versions';

    public const ACTIVITY_VIEW = self::TABS_VIEW . '.activity';

    public static function getSecurityContext(string $webspaceKey): string
    {
        return \sprintf('%s%s.%s', PageAdmin::SECURITY_CONTEXT_PREFIX, $webspaceKey, 'webspace-settings');
    }

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private ContentViewBuilderFactoryInterface $contentViewBuilderFactory,
        private SecurityCheckerInterface $securityChecker,
        private WebspaceManagerInterface $webspaceManager,
        private ActivityViewBuilderFactoryInterface $activityViewBuilderFactory,
    ) {
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $webspace = $this->getFirstViewableWebspace();

        if (null === $webspace) {
            return;
        }

        $resourceKey = WebspaceSettingInterface::RESOURCE_KEY;

        $viewCollection->add(
            $this->viewBuilderFactory
                ->createViewBuilder(self::TABS_VIEW, '/settings/:locale', 'sulu_page.webspace_setting_tabs')
                ->setOption('resourceKey', $resourceKey)
                ->setOption('tabTitle', 'sulu_page.webspace_settings_tab')
                ->setOption('tabOrder', 4096)
                // not the tabCondition, because the child views inherit it and evaluate it against the settings
                ->setOption('webspaceCondition', 'webspaceSettingsForm && settingsPermissions && settingsPermissions.view')
                ->setAttributeDefault('locale', $webspace->getDefaultLocalization()->getLocale())
                ->addRerenderAttribute('webspace')
                ->setParent(PageAdmin::WEBSPACE_TABS_VIEW),
        );

        $viewBuilders = $this->contentViewBuilderFactory->createViews(
            WebspaceSettingInterface::class,
            self::TABS_VIEW,
            toolbarActions: $this->getFormToolbarActions(),
        );

        foreach ($viewBuilders as $viewBuilder) {
            if (self::CONTENT_VIEW !== $viewBuilder->getName()) {
                continue;
            }

            if ($viewBuilder instanceof FormViewBuilderInterface || $viewBuilder instanceof PreviewFormViewBuilderInterface) {
                $viewBuilder->addRouterAttributesToFormMetadata(['webspace']);
            }

            $viewCollection->add($viewBuilder);
        }

        $viewCollection->add(
            $this->viewBuilderFactory
                ->createListViewBuilder(self::VERSIONS_VIEW, '/versions')
                ->setTabTitle('sulu_admin.versions')
                ->setTabOrder(2048)
                ->setResourceKey($resourceKey . '_versions')
                ->setListKey($resourceKey . '_versions')
                ->addListAdapters(['table'])
                ->addAdapterOptions(['table' => ['skin' => 'flat']])
                ->disableTabGap()
                ->disableSearching()
                ->disableSelection()
                ->disableColumnOptions()
                ->disableFiltering()
                ->addRouterAttributesToListRequest(['id', 'webspace'])
                ->addItemActions([
                    new ListItemAction('restore_version', ['success_view' => self::TABS_VIEW]),
                ])
                ->setParent(self::TABS_VIEW),
        );

        if ($this->activityViewBuilderFactory->hasActivityListPermission()) {
            $viewCollection->add(
                $this->activityViewBuilderFactory
                    ->createActivityListViewBuilder(self::ACTIVITY_VIEW, '/activities', $resourceKey)
                    ->setTabOrder(3072)
                    ->setParent(self::TABS_VIEW),
            );
        }
    }

    public function getSecurityContexts()
    {
        $webspaceContexts = [];
        foreach ($this->getSettingsWebspaces() as $webspace) {
            $webspaceContexts[self::getSecurityContext($webspace->getKey())] = self::getSecurityContextPermissions();
        }

        if ([] === $webspaceContexts) {
            return [];
        }

        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                PageAdmin::SECURITY_CONTEXT_GROUP => $webspaceContexts,
            ],
        ];
    }

    public function getSecurityContextsWithPlaceholder()
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                PageAdmin::SECURITY_CONTEXT_GROUP => [
                    self::getSecurityContext('#webspace#') => self::getSecurityContextPermissions(),
                ],
            ],
        ];
    }

    /**
     * @return string[]
     */
    private static function getSecurityContextPermissions(): array
    {
        return [
            PermissionTypes::VIEW,
            PermissionTypes::EDIT,
            PermissionTypes::LIVE,
            PermissionTypes::REVIEW,
        ];
    }

    /**
     * @return array<string, ToolbarAction>
     */
    private function getFormToolbarActions(): array
    {
        $formToolbarActions = $this->contentViewBuilderFactory->getDefaultToolbarActions(WebspaceSettingInterface::class);

        // there is exactly one settings entity per webspace, which uses the form of the webspace, so it can neither be deleted nor be of another type
        unset($formToolbarActions['delete'], $formToolbarActions['type']);

        // keeps the conditions of the default actions, and only replaces the texts which mention pages
        $warningTexts = [
            'sulu_admin.delete_draft' => 'sulu_page.webspace_setting_delete_draft_warning_text',
            'sulu_admin.set_unpublished' => 'sulu_page.webspace_setting_unpublish_warning_text',
        ];
        /** @var ToolbarAction[] $defaultEditToolbarActions */
        $defaultEditToolbarActions = $formToolbarActions['edit']->getOptions()['toolbarActions'];
        $editToolbarActions = [new ToolbarAction('sulu_admin.copy_locale')];
        foreach ($defaultEditToolbarActions as $toolbarAction) {
            $warningText = $warningTexts[$toolbarAction->getType()] ?? null;

            $editToolbarActions[] = null === $warningText
                ? $toolbarAction
                : new ToolbarAction($toolbarAction->getType(), [...$toolbarAction->getOptions(), 'warning_text' => $warningText]);
        }

        $formToolbarActions['edit'] = new DropdownToolbarAction('sulu_admin.edit', 'su-pen', $editToolbarActions);

        return $formToolbarActions;
    }

    /**
     * @return \Generator<Webspace>
     */
    private function getSettingsWebspaces(): \Generator
    {
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            if (null !== $webspace->getWebspaceSettingsForm()) {
                yield $webspace;
            }
        }
    }

    private function getFirstViewableWebspace(): ?Webspace
    {
        foreach ($this->getSettingsWebspaces() as $webspace) {
            if ($this->securityChecker->hasPermission(self::getSecurityContext($webspace->getKey()), PermissionTypes::VIEW)) {
                return $webspace;
            }
        }

        return null;
    }
}
