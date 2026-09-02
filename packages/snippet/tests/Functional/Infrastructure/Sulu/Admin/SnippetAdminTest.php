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

namespace Sulu\Snippet\Tests\Functional\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAdmin;

class SnippetAdminTest extends SuluTestCase
{
    public function testEditToolbarContainsCopyLocaleAction(): void
    {
        $snippetAdmin = $this->getSnippetAdmin();

        $viewCollection = new ViewCollection();
        $snippetAdmin->configureViews($viewCollection);

        $actionNames = $this->collectToolbarActionNames($viewCollection);

        $this->assertContains('sulu_admin.copy_locale', $actionNames);
    }

    public function testEditToolbarContainsCopyAction(): void
    {
        $snippetAdmin = $this->getSnippetAdmin();

        $viewCollection = new ViewCollection();
        $snippetAdmin->configureViews($viewCollection);

        $actionNames = $this->collectToolbarActionNames($viewCollection);

        $this->assertContains('sulu_admin.copy', $actionNames);
    }

    public function testConfigureViewsCreatesAViewPerTemplateGroup(): void
    {
        $viewCollection = new ViewCollection();
        $this->getSnippetAdmin()->configureViews($viewCollection);

        $this->assertTrue($viewCollection->has(SnippetAdmin::LIST_VIEW));

        foreach (['default', 'alternate-group'] as $groupIdentifier) {
            $this->assertTrue($viewCollection->has(SnippetAdmin::LIST_VIEW . '_' . $groupIdentifier));
            $this->assertTrue($viewCollection->has(SnippetAdmin::ADD_TABS_VIEW . '_' . $groupIdentifier));
            $this->assertTrue($viewCollection->has(SnippetAdmin::EDIT_TABS_VIEW . '_' . $groupIdentifier));
        }

        $this->assertFalse($viewCollection->has(SnippetAdmin::EDIT_TABS_VIEW));
    }

    public function testConfigureViewsRestrictsTheListAndFormToTheTemplatesOfTheGroup(): void
    {
        $viewCollection = new ViewCollection();
        $this->getSnippetAdmin()->configureViews($viewCollection);

        $listView = $viewCollection->get(SnippetAdmin::LIST_VIEW . '_alternate-group')->getView();
        $this->assertSame(
            ['templateKeys' => 'snippet-alternate'],
            $listView->getOption('requestParameters'),
        );
        $this->assertSame(
            ['templates' => 'snippet-alternate'],
            $listView->getOption('metadataRequestParameters'),
        );

        $formView = $viewCollection->get(SnippetAdmin::EDIT_TABS_VIEW . '_alternate-group.content')->getView();
        $this->assertSame(
            ['templates' => 'snippet-alternate'],
            $formView->getOption('metadataRequestParameters'),
        );
    }

    public function testGetSecurityContextsRegistersAContextPerCustomGroup(): void
    {
        /** @var array<string, array<string, array<string, string[]>>> $securityContexts */
        $securityContexts = $this->getSnippetAdmin()->getSecurityContexts();

        $snippetContexts = $securityContexts[SnippetAdmin::SULU_ADMIN_SECURITY_SYSTEM]['Snippet'] ?? [];

        $this->assertArrayHasKey(SnippetAdmin::SECURITY_CONTEXT, $snippetContexts);
        $this->assertArrayHasKey(SnippetAdmin::getSnippetSecurityContext('alternate-group'), $snippetContexts);
        $this->assertArrayNotHasKey(SnippetAdmin::getSnippetSecurityContext('default'), $snippetContexts);
    }

    private function getSnippetAdmin(): SnippetAdmin
    {
        /** @var SnippetAdmin $snippetAdmin */
        $snippetAdmin = self::getContainer()->get('sulu_snippet.snippet_admin');

        return $snippetAdmin;
    }

    /**
     * @return string[]
     */
    private function collectToolbarActionNames(ViewCollection $viewCollection): array
    {
        $names = [];

        foreach ($viewCollection->all() as $viewBuilder) {
            $toolbarActions = $viewBuilder->getView()->getOption('toolbarActions');
            if (!\is_array($toolbarActions)) {
                continue;
            }

            foreach ($toolbarActions as $action) {
                if ($action instanceof DropdownToolbarAction) {
                    $subActions = $action->getOptions()['toolbarActions'] ?? [];
                    if (!\is_array($subActions)) {
                        continue;
                    }
                    foreach ($subActions as $sub) {
                        if ($sub instanceof ToolbarAction) {
                            $names[] = $sub->getType();
                        }
                    }

                    continue;
                }

                if ($action instanceof ToolbarAction) {
                    $names[] = $action->getType();
                }
            }
        }

        return $names;
    }
}
