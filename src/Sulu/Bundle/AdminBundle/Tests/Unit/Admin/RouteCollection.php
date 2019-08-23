<?php

namespace Sulu\Bundle\AdminBundle\Tests\Admin;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;

class RouteCollection extends TestCase
{
    /**
     * @var \Sulu\Bundle\AdminBundle\Admin\Routing\RouteCollection
     */
    private $routeCollection;

    public function setUp()
    {
        $this->routeCollection = new RouteCollection();
    }

    public function testAddRoutes()
    {
        $route1 = new Route();
    }
}
