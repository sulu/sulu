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

use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAdmin;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAreaAdmin;

class SnippetAreaAdminTest extends SuluTestCase
{
    public function testConfigureViewsMapsEveryTemplateToItsGroupEditView(): void
    {
        /** @var SnippetAreaAdmin $snippetAreaAdmin */
        $snippetAreaAdmin = self::getContainer()->get('sulu_snippet.snippet_area_admin');

        $viewCollection = new ViewCollection();
        $snippetAreaAdmin->configureViews($viewCollection);

        $view = $viewCollection->get('sulu_snippet.snippet_areas')->getView();

        $snippetEditViews = $view->getOption('snippetEditViews');
        $this->assertIsArray($snippetEditViews);

        $this->assertSame(SnippetAdmin::EDIT_TABS_VIEW . '_default', $snippetEditViews['snippet'] ?? null);
        $this->assertSame(
            SnippetAdmin::EDIT_TABS_VIEW . '_alternate-group',
            $snippetEditViews['snippet-alternate'] ?? null,
        );
    }
}
