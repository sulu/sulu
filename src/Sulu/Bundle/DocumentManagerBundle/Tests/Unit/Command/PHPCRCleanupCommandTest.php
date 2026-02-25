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

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Command;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\DocumentManagerBundle\Command\PHPCRCleanupCommand;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetter;

class PHPCRCleanupCommandTest extends TestCase
{
    use ProphecyTrait;

    private $session;
    private $webspaceManager;
    private PHPCRCleanupCommand $command;

    protected function setUp(): void
    {
        $this->session = $this->prophesize(SessionInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $servicesResetter = $this->prophesize(ServicesResetter::class);

        $this->command = new PHPCRCleanupCommand(
            $this->session->reveal(),
            $this->webspaceManager->reveal(),
            $servicesResetter->reveal(),
            '/tmp/test-project',
        );
    }

    public function testGetOrphanedWebspaceKeys(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');

        $collection = new WebspaceCollection();
        $collection->setWebspaces(['sulu_io' => $webspace]);
        $this->webspaceManager->getWebspaceCollection()->willReturn($collection);

        $this->session->nodeExists('/cmf')->willReturn(true);
        $cmfNode = $this->prophesize(NodeInterface::class);
        $this->session->getNode('/cmf')->willReturn($cmfNode->reveal());

        $suluIoNode = $this->prophesize(NodeInterface::class);
        $suluIoNode->getName()->willReturn('sulu_io');

        $removedNode = $this->prophesize(NodeInterface::class);
        $removedNode->getName()->willReturn('old_webspace');
        $removedNode->hasNode('contents')->willReturn(true);

        $snippetsNode = $this->prophesize(NodeInterface::class);
        $snippetsNode->getName()->willReturn('snippets');

        $articlesNode = $this->prophesize(NodeInterface::class);
        $articlesNode->getName()->willReturn('articles');

        $cmfNode->getNodes()->willReturn([
            $suluIoNode->reveal(),
            $removedNode->reveal(),
            $snippetsNode->reveal(),
            $articlesNode->reveal(),
        ]);

        $result = $this->command->getOrphanedWebspaceKeys();

        $this->assertSame(['old_webspace'], $result);
    }

    public function testGetOrphanedWebspaceKeysNoneOrphaned(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');

        $collection = new WebspaceCollection();
        $collection->setWebspaces(['sulu_io' => $webspace]);
        $this->webspaceManager->getWebspaceCollection()->willReturn($collection);

        $this->session->nodeExists('/cmf')->willReturn(true);
        $cmfNode = $this->prophesize(NodeInterface::class);
        $this->session->getNode('/cmf')->willReturn($cmfNode->reveal());

        $suluIoNode = $this->prophesize(NodeInterface::class);
        $suluIoNode->getName()->willReturn('sulu_io');

        $snippetsNode = $this->prophesize(NodeInterface::class);
        $snippetsNode->getName()->willReturn('snippets');

        $cmfNode->getNodes()->willReturn([
            $suluIoNode->reveal(),
            $snippetsNode->reveal(),
        ]);

        $result = $this->command->getOrphanedWebspaceKeys();

        $this->assertSame([], $result);
    }

    public function testGetOrphanedWebspaceKeysSkipsNonWebspaceNodes(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');

        $collection = new WebspaceCollection();
        $collection->setWebspaces(['sulu_io' => $webspace]);
        $this->webspaceManager->getWebspaceCollection()->willReturn($collection);

        $this->session->nodeExists('/cmf')->willReturn(true);
        $cmfNode = $this->prophesize(NodeInterface::class);
        $this->session->getNode('/cmf')->willReturn($cmfNode->reveal());

        $suluIoNode = $this->prophesize(NodeInterface::class);
        $suluIoNode->getName()->willReturn('sulu_io');

        // Unknown node without 'contents' child should NOT be treated as orphaned webspace
        $customNode = $this->prophesize(NodeInterface::class);
        $customNode->getName()->willReturn('custom_data');
        $customNode->hasNode('contents')->willReturn(false);

        $cmfNode->getNodes()->willReturn([
            $suluIoNode->reveal(),
            $customNode->reveal(),
        ]);

        $result = $this->command->getOrphanedWebspaceKeys();

        $this->assertSame([], $result);
    }

    public function testGetOrphanedWebspaceKeysNoCmfNode(): void
    {
        $this->session->nodeExists('/cmf')->willReturn(false);

        $result = $this->command->getOrphanedWebspaceKeys();

        $this->assertSame([], $result);
    }

    public function testGetStaleRouteLocales(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $deLocalization = new Localization('de');
        $enLocalization = new Localization('en');
        $webspace->setLocalizations([$deLocalization, $enLocalization]);

        $collection = new WebspaceCollection();
        $collection->setWebspaces([$webspace]);
        $this->webspaceManager->getWebspaceCollection()->willReturn($collection);
        $this->webspaceManager->getAllLocalesByWebspaces()->willReturn([
            'sulu_io' => ['de' => $deLocalization, 'en' => $enLocalization],
        ]);

        $routesNode = $this->prophesize(NodeInterface::class);
        $this->session->nodeExists('/cmf/sulu_io/routes')->willReturn(true);
        $this->session->getNode('/cmf/sulu_io/routes')->willReturn($routesNode->reveal());

        $deNode = $this->prophesize(NodeInterface::class);
        $deNode->getName()->willReturn('de');

        $enNode = $this->prophesize(NodeInterface::class);
        $enNode->getName()->willReturn('en');

        $frNode = $this->prophesize(NodeInterface::class);
        $frNode->getName()->willReturn('fr');

        $routesNode->getNodes()->willReturn([
            $deNode->reveal(),
            $enNode->reveal(),
            $frNode->reveal(),
        ]);

        $result = $this->command->getStaleRouteLocales();

        $this->assertSame(['sulu_io' => ['fr']], $result);
    }
}
