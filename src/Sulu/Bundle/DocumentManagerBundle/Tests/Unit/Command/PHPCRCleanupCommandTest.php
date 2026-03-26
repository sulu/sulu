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
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Command\PHPCRCleanupCommand;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetter;

class PHPCRCleanupCommandTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<SessionInterface> */
    private ObjectProphecy $liveSession;
    /** @var ObjectProphecy<SessionInterface> */
    private ObjectProphecy $session;
    /** @var ObjectProphecy<WebspaceManagerInterface> */
    private ObjectProphecy $webspaceManager;
    private PHPCRCleanupCommand $command;

    protected function setUp(): void
    {
        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->session = $this->prophesize(SessionInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $servicesResetter = $this->prophesize(ServicesResetter::class);

        $this->command = new PHPCRCleanupCommand(
            $this->liveSession->reveal(),
            $this->session->reveal(),
            $this->webspaceManager->reveal(),
            $servicesResetter->reveal(),
            '/tmp/test-project',
        );
    }

    public function testGetOrphanedWebspaceKeys(): void
    {
        $this->configureSuluIoWebspaceCollection();

        $this->session->nodeExists('/cmf')->willReturn(true);
        $this->liveSession->nodeExists('/cmf')->willReturn(false);
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

    public function testGetOrphanedWebspaceKeysIncludesLiveOnlyOrphans(): void
    {
        $this->configureSuluIoWebspaceCollection();

        $this->session->nodeExists('/cmf')->willReturn(false);
        $this->liveSession->nodeExists('/cmf')->willReturn(true);
        $cmfNode = $this->prophesize(NodeInterface::class);
        $this->liveSession->getNode('/cmf')->willReturn($cmfNode->reveal());

        $suluIoNode = $this->prophesize(NodeInterface::class);
        $suluIoNode->getName()->willReturn('sulu_io');

        $removedNode = $this->prophesize(NodeInterface::class);
        $removedNode->getName()->willReturn('old_webspace');
        $removedNode->hasNode('contents')->willReturn(true);

        $cmfNode->getNodes()->willReturn([
            $suluIoNode->reveal(),
            $removedNode->reveal(),
        ]);

        $result = $this->command->getOrphanedWebspaceKeys();

        $this->assertSame(['old_webspace'], $result);
    }

    public function testGetOrphanedWebspaceKeysDetectsRoutesOnlyTree(): void
    {
        $this->configureSuluIoWebspaceCollection();

        $this->session->nodeExists('/cmf')->willReturn(true);
        $this->liveSession->nodeExists('/cmf')->willReturn(false);
        $cmfNode = $this->prophesize(NodeInterface::class);
        $this->session->getNode('/cmf')->willReturn($cmfNode->reveal());

        $suluIoNode = $this->prophesize(NodeInterface::class);
        $suluIoNode->getName()->willReturn('sulu_io');

        $removedNode = $this->prophesize(NodeInterface::class);
        $removedNode->getName()->willReturn('old_webspace');
        $removedNode->hasNode('contents')->willReturn(false);
        $removedNode->hasNode('routes')->willReturn(true);

        $cmfNode->getNodes()->willReturn([
            $suluIoNode->reveal(),
            $removedNode->reveal(),
        ]);

        $result = $this->command->getOrphanedWebspaceKeys();

        $this->assertSame(['old_webspace'], $result);
    }

    public function testGetOrphanedWebspaceKeysNoneOrphaned(): void
    {
        $this->configureSuluIoWebspaceCollection();

        $this->session->nodeExists('/cmf')->willReturn(true);
        $this->liveSession->nodeExists('/cmf')->willReturn(false);
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
        $this->configureSuluIoWebspaceCollection();

        $this->session->nodeExists('/cmf')->willReturn(true);
        $this->liveSession->nodeExists('/cmf')->willReturn(false);
        $cmfNode = $this->prophesize(NodeInterface::class);
        $this->session->getNode('/cmf')->willReturn($cmfNode->reveal());

        $suluIoNode = $this->prophesize(NodeInterface::class);
        $suluIoNode->getName()->willReturn('sulu_io');

        // Unknown node without 'contents' or 'routes' child should NOT be treated as orphaned webspace
        $customNode = $this->prophesize(NodeInterface::class);
        $customNode->getName()->willReturn('custom_data');
        $customNode->hasNode('contents')->willReturn(false);
        $customNode->hasNode('routes')->willReturn(false);

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
        $this->liveSession->nodeExists('/cmf')->willReturn(false);

        $result = $this->command->getOrphanedWebspaceKeys();

        $this->assertSame([], $result);
    }

    public function testGetStaleRouteLocales(): void
    {
        $deLocalization = new Localization('de');
        $enLocalization = new Localization('en');
        $this->configureSuluIoWebspaceCollection([$deLocalization, $enLocalization]);
        $this->webspaceManager->getAllLocalesByWebspaces()->willReturn([
            'sulu_io' => ['de' => $deLocalization, 'en' => $enLocalization],
        ]);

        $routesNode = $this->prophesize(NodeInterface::class);
        $this->session->nodeExists('/cmf/sulu_io/routes')->willReturn(true);
        $this->liveSession->nodeExists('/cmf/sulu_io/routes')->willReturn(false);
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

    public function testGetStaleRouteLocalesIncludesLiveOnlyRoutes(): void
    {
        $deLocalization = new Localization('de');
        $enLocalization = new Localization('en');
        $this->configureSuluIoWebspaceCollection([$deLocalization, $enLocalization]);
        $this->webspaceManager->getAllLocalesByWebspaces()->willReturn([
            'sulu_io' => ['de' => $deLocalization, 'en' => $enLocalization],
        ]);

        $this->session->nodeExists('/cmf/sulu_io/routes')->willReturn(false);
        $this->liveSession->nodeExists('/cmf/sulu_io/routes')->willReturn(true);

        $routesNode = $this->prophesize(NodeInterface::class);
        $this->liveSession->getNode('/cmf/sulu_io/routes')->willReturn($routesNode->reveal());

        $deNode = $this->prophesize(NodeInterface::class);
        $deNode->getName()->willReturn('de');

        $frNode = $this->prophesize(NodeInterface::class);
        $frNode->getName()->willReturn('fr');

        $routesNode->getNodes()->willReturn([
            $deNode->reveal(),
            $frNode->reveal(),
        ]);

        $result = $this->command->getStaleRouteLocales();

        $this->assertSame(['sulu_io' => ['fr']], $result);
    }

    /**
     * @param Localization[] $localizations
     */
    private function configureSuluIoWebspaceCollection(array $localizations = []): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        if ([] !== $localizations) {
            $webspace->setLocalizations($localizations);
        }

        $collection = new WebspaceCollection();
        $collection->setWebspaces(['sulu_io' => $webspace]);
        $this->webspaceManager->getWebspaceCollection()->willReturn($collection);
    }
}
