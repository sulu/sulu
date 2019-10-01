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
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteCollection;

class SearchAdmin extends Admin
{
    const SEARCH_ROUTE = 'sulu_search.search';
    /**
     * @var RouteBuilderFactoryInterface
     */
    private $routeBuilderFactory;

    public function __construct(RouteBuilderFactoryInterface $routeBuilderFactory)
    {
        $this->routeBuilderFactory = $routeBuilderFactory;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        $search = new NavigationItem('sulu_search.search');
        $search->setPosition(0);
        $search->setIcon('su-search');
        $search->setMainRoute(static::SEARCH_ROUTE);

        $navigationItemCollection->add($search);
    }

    public function configureViews(RouteCollection $routeCollection): void
    {
        $routeCollection->add(
            $this->routeBuilderFactory->createRouteBuilder(static::SEARCH_ROUTE, '/', 'sulu_search.search')
        );
    }
}
