<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Bundle\WebsiteBundle\Controller\DefaultController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class DefaultControllerTest extends \PHPUnit_Framework_TestCase
{
    public function provideForwardData()
    {
        return [
            ['SuluWebsiteBundle:Redirect:redirectWebspace', 'redirectWebspaceAction'],
            ['SuluWebsiteBundle:Redirect:redirect', 'redirectAction'],
        ];
    }

    /**
     * @dataProvider provideForwardData
     */
    public function testForward($controller, $method)
    {
        $query = [];
        $attributes = [];

        $subRequest = $this->prophesize(Request::class);

        $attributesBag = $this->prophesize(ParameterBag::class);
        $attributesBag->all()->willReturn($attributes);

        $queryBag = $this->prophesize(ParameterBag::class);
        $queryBag->all()->willReturn($query);

        $request = $this->prophesize(Request::class);
        $request->duplicate(
            $query,
            null,
            array_merge($attributes, ['_controller' => $controller])
        )->willReturn($subRequest->reveal());
        $request->reveal()->attributes = $attributesBag->reveal();
        $request->reveal()->query = $queryBag->reveal();

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request->reveal());

        $httpKernel = $this->prophesize(HttpKernelInterface::class);
        $httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST)->shouldBeCalledTimes(1);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('request_stack')->willReturn($requestStack->reveal());
        $container->get('http_kernel')->willReturn($httpKernel->reveal());

        $controller = new DefaultController();
        $controller->setContainer($container->reveal());

        $controller->{$method}($request->reveal());
    }
}
