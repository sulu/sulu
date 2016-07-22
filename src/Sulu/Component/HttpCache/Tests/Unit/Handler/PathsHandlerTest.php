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

use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use Prophecy\Argument;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\Handler\PathsHandler;
use Sulu\Component\HttpCache\HandlerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PathsHandlerTest extends \PHPUnit_Framework_TestCase
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
     * @var string
     */
    private $env = 'prod';

    /**
     * @var string
     */
    private $languageCode = 'en';

    /**
     * @var string
     */
    private $webspaceKey = 'sulu_io';

    /**
     * @var string
     */
    private $host = 'sulu.io';

    public function setUp()
    {
        parent::setUp();

        $this->structure = $this->prophesize(StructureInterface::class);

        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->proxyClient = $this->prophesize(PurgeInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->request = $this->prophesize(Request::class);
        $this->replacer = $this->prophesize(ReplacerInterface::class);

        $this->request->getHost()->willReturn($this->host);
        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());

        $this->handler = new PathsHandler(
            $this->webspaceManager->reveal(),
            $this->proxyClient->reveal(),
            $this->requestStack->reveal(),
            $this->replacer->reveal(),
            $this->env
        );
    }

    public function testInvalidateStructureNoRlp()
    {
        $this->structure->hasTag('sulu.rlp')->willReturn(false);
        $this->webspaceManager->findUrlsByResourceLocator(Argument::any())->shouldNotBeCalled();
        $this->replacer->replaceHost(Argument::any())->shouldNotBeCalled();
        $this->handler->invalidateStructure($this->structure->reveal());
        $this->handler->flush();
    }

    public function testInvalidateStructureRlpNull()
    {
        $this->structure->hasTag('sulu.rlp')->willReturn(true);
        $this->structure->getPropertyValueByTagName('sulu.rlp')->willReturn(null);
        $this->webspaceManager->findUrlsByResourceLocator(Argument::any())->shouldNotBeCalled();
        $this->replacer->replaceHost(Argument::any())->shouldNotBeCalled();
        $this->handler->invalidateStructure($this->structure->reveal());
        $this->handler->flush();
    }

    public function testInvalidateRequestNull()
    {
        $this->requestStack->getCurrentRequest()->willReturn(null);
        $this->handler = new PathsHandler(
            $this->webspaceManager->reveal(),
            $this->proxyClient->reveal(),
            $this->requestStack->reveal(),
            $this->replacer->reveal(),
            $this->env
        );

        $this->structure->hasTag('sulu.rlp')->willReturn(true);
        $this->structure->getPropertyValueByTagName('sulu.rlp')->willReturn('/path/to');
        $this->structure->getLanguageCode()->willReturn($this->languageCode);
        $this->structure->getWebspaceKey()->willReturn($this->webspaceKey);

        $urls = [
            '/path/to/1',
            '/path/to/2',
        ];

        $this->webspaceManager->findUrlsByResourceLocator(
            '/path/to',
            $this->env,
            $this->languageCode,
            $this->webspaceKey
        )->willReturn($urls);
        $this->webspaceManager->findUrlsByResourceLocator(Argument::any())->shouldNotBeCalled();

        $this->replacer->replaceHost(Argument::any())->shouldNotBeCalled();

        $this->proxyClient->purge($urls[0])->shouldBeCalled();
        $this->proxyClient->purge($urls[1])->shouldBeCalled();
        $this->proxyClient->flush()->shouldBeCalled();

        $this->handler->invalidateStructure($this->structure->reveal());
        $this->handler->flush();
    }

    public function testInvalidate()
    {
        $this->structure->hasTag('sulu.rlp')->willReturn(true);
        $this->structure->getPropertyValueByTagName('sulu.rlp')->willReturn('/path/to');
        $this->structure->getLanguageCode()->willReturn($this->languageCode);
        $this->structure->getWebspaceKey()->willReturn($this->webspaceKey);

        $genericUrls = [
            '{host}/path/to/1',
            '{host}/path/to/2',
        ];

        $concreteUrls = [
            'sulu.io/path/to/1',
            'sulu.io/path/to/2',
        ];

        $this->webspaceManager->findUrlsByResourceLocator(
            '/path/to',
            $this->env,
            $this->languageCode,
            $this->webspaceKey
        )->willReturn($genericUrls);
        $this->webspaceManager->findUrlsByResourceLocator(Argument::any())->shouldNotBeCalled();

        $this->replacer->replaceHost($genericUrls[0], $this->host)->willReturn($concreteUrls[0]);
        $this->replacer->replaceHost($genericUrls[1], $this->host)->willReturn($concreteUrls[1]);
        $this->replacer->replaceHost(Argument::any())->shouldNotBeCalled();

        $this->proxyClient->purge($concreteUrls[0])->shouldBeCalled();
        $this->proxyClient->purge($concreteUrls[1])->shouldBeCalled();
        $this->proxyClient->flush()->shouldBeCalled();

        $this->handler->invalidateStructure($this->structure->reveal());
        $this->handler->flush();
    }
}
