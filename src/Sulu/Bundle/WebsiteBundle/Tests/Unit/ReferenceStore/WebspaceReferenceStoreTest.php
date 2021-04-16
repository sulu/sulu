<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\ReferenceStore;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\WebspaceReferenceStore;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;

class WebspaceReferenceStoreTest extends TestCase
{
    private function createReferenceStore(?RequestAnalyzerInterface $requestAnalyzer = null): WebspaceReferenceStore
    {
        return new WebspaceReferenceStore($requestAnalyzer);
    }

    public function testAdd(): void
    {
        $this->expectException(\LogicException::class);

        $store = $this->createReferenceStore();

        $store->add('123-123-123');
    }

    public function testGetAllWithoutRequestAnalyzer(): void
    {
        $store = $this->createReferenceStore();

        $this->assertEquals([], $store->getAll());
    }

    public function testGetAllWithoutWebspace(): void
    {
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzer->getWebspace()->willReturn(null);

        $store = $this->createReferenceStore($requestAnalyzer->reveal());

        $this->assertEquals([], $store->getAll());
    }

    public function testGetAll(): void
    {
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu');

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzer->getWebspace()->willReturn($webspace->reveal());

        $store = $this->createReferenceStore($requestAnalyzer->reveal());

        $this->assertEquals(['sulu'], $store->getAll());
    }
}
