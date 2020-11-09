<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

class ContentRouteProviderTest extends WebsiteTestCase
{
    public function testRouteIncludesUtf8Option()
    {
        static::initPhpcr();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => 'sulu.lo']);

        // set _sulu attributes via RequestAnalyzer
        $requestAnalyzer = static::getContainer()->get('sulu_core.webspace.request_analyzer');
        $requestAnalyzer->analyze($request);
        $requestAnalyzer->validate($request);

        $routeProvider = static::getContainer()->get('sulu_website.provider.content');
        $collection = $routeProvider->getRouteCollectionForRequest($request);
        $routes = array_values($collection->all());

        $this->assertCount(1, $routes);
        $this->assertTrue($routes[0]->getOption('utf8')); // see https://github.com/sulu/sulu/pull/5561
    }
}
