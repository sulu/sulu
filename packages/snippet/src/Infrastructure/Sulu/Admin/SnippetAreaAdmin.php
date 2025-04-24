<?php

declare(strict_types=1);

namespace Sulu\Snippet\Infrastructure\Sulu\Admin;

use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;

class SnippetAreaAdmin extends Admin
{
    public const SECURITY_CONTEXT = 'sulu.snippet.snippet_areas';

    public const LIST_VIEW = 'sulu_snippet.snippet_areas.list';

    public function __construct(
      private ViewBuilderFactoryInterface $viewBuilderFactory,
      private SecurityCheckerInterface $securityChecker,
    ) {
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $viewCollection->add(
            $this->viewBuilderFactory
                ->createViewBuilder('sulu_snippet.snippet_areas', '/snippet-areas', 'sulu_snippet.snippet_areas')
                ->setOption('snippetEditView', SnippetAdmin::EDIT_TABS_VIEW)
                ->setOption('tabTitle', 'sulu_snippet.default_snippets')
                ->setOption('tabOrder', 3072)
                ->setParent(PageAdmin::WEBSPACE_TABS_VIEW)
                ->addRerenderAttribute('webspace')
        );
    }
}
