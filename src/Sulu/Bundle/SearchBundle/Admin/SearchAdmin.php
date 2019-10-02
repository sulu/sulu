<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;

class SearchAdmin extends Admin
{
    const SEARCH_VIEW = 'sulu_search.search';
    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    public function __construct(ViewBuilderFactoryInterface $viewBuilderFactory)
    {
        $this->viewBuilderFactory = $viewBuilderFactory;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        $search = new NavigationItem('sulu_search.search');
        $search->setPosition(0);
        $search->setIcon('su-search');
        $search->setView(static::SEARCH_VIEW);

        $navigationItemCollection->add($search);
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $viewCollection->add(
            $this->viewBuilderFactory->createViewBuilder(static::SEARCH_VIEW, '/', 'sulu_search.search')
        );
    }
}
