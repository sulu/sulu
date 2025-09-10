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

namespace Sulu\Component\Webspace\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\EventSubscriber\WebspaceTagSubscriber;
use Sulu\Component\Webspace\Webspace;

class WebspaceTagSubscriberTest extends TestCase
{
    use ProphecyTrait;

    private WebspaceTagSubscriber $webspaceTagSubscriber;
    private ReferenceStore $referenceStore;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    protected function setUp(): void
    {
        $this->referenceStore = new ReferenceStore();
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->webspaceTagSubscriber = new WebspaceTagSubscriber(
            $this->referenceStore,
            $this->requestAnalyzer->reveal()
        );
    }

    public function testAddWebspaceTag(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('example');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->webspaceTagSubscriber->addWebspaceTag();

        $tags = $this->referenceStore->getAll();
        self::assertArrayHasKey('webspace-example', $tags);
        self::assertEquals('webspace-example', $tags['webspace-example']);
        self::assertCount(1, $tags);
    }

    public function testAddWebspaceTagWithoutWebspace(): void
    {
        $this->requestAnalyzer->getWebspace()->willReturn(null);

        $this->webspaceTagSubscriber->addWebspaceTag();

        $tags = $this->referenceStore->getAll();
        self::assertEmpty($tags);
    }

    public function testAddWebspaceTagWithDifferentWebspaces(): void
    {
        // First webspace
        $webspace1 = new Webspace();
        $webspace1->setKey('example');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace1);
        $this->webspaceTagSubscriber->addWebspaceTag();

        // Second webspace
        $webspace2 = new Webspace();
        $webspace2->setKey('sulu');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace2);
        $this->webspaceTagSubscriber->addWebspaceTag();

        // Verify both webspace tags are present
        $tags = $this->referenceStore->getAll();
        self::assertArrayHasKey('webspace-example', $tags);
        self::assertArrayHasKey('webspace-sulu', $tags);
        self::assertCount(2, $tags);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = WebspaceTagSubscriber::getSubscribedEvents();

        self::assertArrayHasKey('kernel.response', $events);
        self::assertEquals(['addWebspaceTag', 2048], $events['kernel.response']);
    }
}
