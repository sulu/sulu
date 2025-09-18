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

namespace Sulu\Snippet\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;

/**
 * @final
 */
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
        if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            return;
        }

        $viewCollection->add(
            $this->viewBuilderFactory
                ->createViewBuilder('sulu_snippet.snippet_areas', '/snippet-areas', 'sulu_snippet.snippet_areas')
                ->setOption('snippetEditView', SnippetAdmin::EDIT_TABS_VIEW)
                ->setOption('tabTitle', 'sulu_snippet.webspace_default_snippets')
                ->setOption('tabOrder', 3072)
                ->setParent(PageAdmin::WEBSPACE_TABS_VIEW)
                ->addRerenderAttribute('webspace')
        );
    }

    /**
     * @return mixed[]
     */
    public function getSecurityContexts(): array
    {
        return [
            'Sulu' => [
                'Global' => [
                    static::SECURITY_CONTEXT => [
                        PermissionTypes::EDIT,
                    ],
                ],
            ],
        ];
    }
}
