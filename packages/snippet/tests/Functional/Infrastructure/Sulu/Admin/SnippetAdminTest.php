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
