<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Prophecy\Argument;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationAliasNotFoundException;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationRegistryInterface;
use Sulu\Component\Rest\Exception\RestException;
use Symfony\Component\HttpFoundation\Request;

class ContentNavigationControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentNavigationController
     */
    private $contentNavigationController;

    /**
     * @var ContentNavigationRegistryInterface
     */
    private $contentNavigationCollector;

    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    public function setUp()
    {
        $this->contentNavigationCollector = $this->prophesize(ContentNavigationRegistryInterface::class);
        $this->viewHandler = $this->prophesize(ViewHandlerInterface::class);

        $this->contentNavigationController = new ContentNavigationController(
            $this->contentNavigationCollector->reveal(),
            $this->viewHandler->reveal()
        );
    }

    public function testCGetAction()
    {
        $query = ['alias' => 'alias', 'option' => 'value'];
        $data = [new ContentNavigationItem('item')];
        $request = new Request($query);

        $this->contentNavigationCollector->getNavigationItems($query['alias'], $query)->willReturn($data);

        $this->viewHandler->handle(View::create($data))->shouldBeCalled();

        $this->contentNavigationController->cgetAction($request);
    }

    public function testCGetActionWithoutAlias()
    {
        $request = new Request();

        $exception = new RestException('The alias attribute is required to load the content navigation');
        $this->viewHandler->handle(View::create($exception->toArray(), 400))->shouldBeCalled();

        $this->contentNavigationController->cgetAction($request);
    }

    public function testCGetActionWithUnexistingAlias()
    {
        $query = ['alias' => 'not_existent_alias'];
        $request = new Request($query);

        $contentNavigationAliasNotFoundException = new ContentNavigationAliasNotFoundException(
            $query['alias'], []
        );
        $this->contentNavigationCollector->getNavigationItems(Argument::cetera())->willThrow(
            $contentNavigationAliasNotFoundException
        );

        $exception = new RestException(
            $contentNavigationAliasNotFoundException->getMessage(),
            0,
            $contentNavigationAliasNotFoundException
        );
        $this->viewHandler->handle(View::create($exception->toArray(), 404))->shouldBeCalled();

        $this->contentNavigationController->cgetAction($request);
    }
}
