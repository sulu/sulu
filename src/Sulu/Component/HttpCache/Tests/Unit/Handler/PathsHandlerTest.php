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

use Prophecy\Argument;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\Handler\PathsHandler;
use Sulu\Component\HttpCache\HandlerInterface;

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

    public function setUp()
    {
        parent::setUp();

        $this->structure = $this->prophesize('Sulu\Component\Content\Compat\StructureInterface');

        $this->webspaceManager = $this->prophesize('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
        $this->proxyClient = $this->prophesize('FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface');

        $this->handler = new PathsHandler(
            $this->webspaceManager->reveal(),
            $this->proxyClient->reveal(),
            $this->env
        );
    }

    public function testInvalidateStructureNoRlp()
    {
        $this->structure->hasTag('sulu.rlp')->willReturn(false);
        $this->webspaceManager->findUrlsByResourceLocator(Argument::any())->shouldNotBeCalled();
        $this->handler->invalidateStructure($this->structure->reveal());
        $this->handler->flush();
    }

    public function testInvalidateStructureoRlpNull()
    {
        $this->structure->hasTag('sulu.rlp')->willReturn(true);
        $this->structure->getPropertyValueByTagName('sulu.rlp')->willReturn(null);
        $this->webspaceManager->findUrlsByResourceLocator(Argument::any())->shouldNotBeCalled();
        $this->handler->invalidateStructure($this->structure->reveal());
        $this->handler->flush();
    }

    public function testInvalidate()
    {
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

        $this->proxyClient->purge($urls[0])->shouldBeCalled();
        $this->proxyClient->purge($urls[1])->shouldBeCalled();
        $this->proxyClient->flush()->shouldBeCalled();

        $this->webspaceManager->findUrlsByResourceLocator(Argument::any())->shouldNotBeCalled();

        $this->handler->invalidateStructure($this->structure->reveal());
        $this->handler->flush();
    }
}
