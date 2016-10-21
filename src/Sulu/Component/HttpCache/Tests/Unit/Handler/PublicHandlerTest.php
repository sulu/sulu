<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Tests\Unit\Handler;

use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\CacheLifetimeResolverInterface;
use Sulu\Component\HttpCache\Handler\PublicHandler;
use Sulu\Component\HttpCache\HandlerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

class PublicHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var int
     */
    private $maxAge = 10;

    /**
     * @var int
     */
    private $sharedMaxAge = 10;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var CacheLifetimeResolverInterface
     */
    private $cacheLifetimeResolver;

    /**
     * @var mixed
     */
    private $parameterBag;

    public function setUp()
    {
        $this->structure = $this->prophesize(PageBridge::class);
        $this->parameterBag = $this->prophesize(ParameterBag::class);
        $this->response = $this->prophesize(Response::class);
        $this->cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $this->response->headers = $this->parameterBag;

        $this->handler = new PublicHandler(
            $this->cacheLifetimeResolver->reveal(),
            $this->maxAge,
            $this->sharedMaxAge,
            true
        );
    }

    public function testUpdateResponse()
    {
        $this->response->setPublic()->shouldBeCalled();
        $this->response->setMaxAge($this->maxAge)->shouldBeCalled();
        $this->response->setSharedMaxAge($this->sharedMaxAge)->shouldBeCalled();
        $this->structure->getCacheLifeTime()
            ->willReturn(['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 10]);
        $this->cacheLifetimeResolver->resolve(CacheLifetimeResolverInterface::TYPE_SECONDS, 10)->willReturn(10);
        $this->response->getAge()->willReturn(50);

        $this->handler->updateResponse(
            $this->response->reveal(),
            $this->structure->reveal()
        );
    }

    public function testDisableCache()
    {
        // disable cache
        $this->structure->getCacheLifeTime()
            ->willReturn(['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => 0]);
        $this->cacheLifetimeResolver->resolve(CacheLifetimeResolverInterface::TYPE_SECONDS, 0)->willReturn(0);

        $this->response->setPublic()->shouldNotBeCalled();
        $this->response->setMaxAge($this->maxAge)->shouldNotBeCalled();
        $this->response->setSharedMaxAge($this->sharedMaxAge)->shouldNotBeCalled();

        $this->handler->updateResponse(
            $this->response->reveal(),
            $this->structure->reveal()
        );
    }
}
