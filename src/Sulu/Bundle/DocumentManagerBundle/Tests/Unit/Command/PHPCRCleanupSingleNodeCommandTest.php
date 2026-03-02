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
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Command\PHPCRCleanupSingleNodeCommand;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PHPCRCleanupSingleNodeCommandTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<SessionInterface> */
    private ObjectProphecy $liveSession;
    /** @var ObjectProphecy<SessionInterface> */
    private ObjectProphecy $session;
    /** @var ObjectProphecy<StructureMetadataFactoryInterface> */
    private ObjectProphecy $structureMetadataFactory;
    /** @var ObjectProphecy<NamespaceRegistry> */
    private ObjectProphecy $namespaceRegistry;
    /** @var ObjectProphecy<EventDispatcherInterface> */
    private ObjectProphecy $eventDispatcher;
    /** @var ObjectProphecy<DocumentManagerInterface> */
    private ObjectProphecy $documentManager;
    /** @var ObjectProphecy<WebspaceManagerInterface> */
    private ObjectProphecy $webspaceManager;
    private PHPCRCleanupSingleNodeCommand $command;

    protected function setUp(): void
    {
        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->session = $this->prophesize(SessionInterface::class);
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->namespaceRegistry = $this->prophesize(NamespaceRegistry::class);
        $this->namespaceRegistry->getPrefix('system_localized')->willReturn('i18n');
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->command = new PHPCRCleanupSingleNodeCommand(
            $this->liveSession->reveal(),
            $this->session->reveal(),
            $this->structureMetadataFactory->reveal(),
            $this->namespaceRegistry->reveal(),
            $this->eventDispatcher->reveal(),
            $this->documentManager->reveal(),
            [['phpcr_type' => 'sulu:page', 'alias' => 'page']],
            $this->webspaceManager->reveal(),
        );
    }

    public function testGetValidLocalesForPageNode(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn('/cmf/sulu_io/contents/page-1');

        $deLocalization = new Localization('de');
        $enLocalization = new Localization('en');

        $this->webspaceManager->getAllLocalesByWebspaces()->willReturn([
            'sulu_io' => ['de' => $deLocalization, 'en' => $enLocalization],
        ]);

        $result = $this->command->getValidLocales($node->reveal());

        $this->assertEqualsCanonicalizing(['de', 'en'], $result);
    }

    public function testGetValidLocalesForSnippetNode(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn('/cmf/snippets/hotel/snippet-1');

        $this->webspaceManager->getAllLocales()->willReturn(['de', 'en', 'fr']);

        $result = $this->command->getValidLocales($node->reveal());

        $this->assertEqualsCanonicalizing(['de', 'en', 'fr'], $result);
    }

    public function testGetValidLocalesForArticleNode(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn('/cmf/articles/default/some-uuid/article-1');

        $this->webspaceManager->getAllLocales()->willReturn(['de', 'en']);

        $result = $this->command->getValidLocales($node->reveal());

        $this->assertEqualsCanonicalizing(['de', 'en'], $result);
    }

    public function testGetValidLocalesForRemovedWebspace(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn('/cmf/removed_webspace/contents/page-1');

        $this->webspaceManager->getAllLocalesByWebspaces()->willReturn([
            'sulu_io' => ['de' => new Localization('de')],
        ]);

        $result = $this->command->getValidLocales($node->reveal());

        $this->assertSame([], $result);
    }

    public function testRemoveStaleLocaleProperties(): void
    {
        $node = $this->prophesize(NodeInterface::class);

        $staleProperty1 = $this->prophesize(PropertyInterface::class);
        $staleProperty1->getName()->willReturn('i18n:fr-template');
        $staleProperty1->remove()->shouldBeCalledOnce();

        $staleProperty2 = $this->prophesize(PropertyInterface::class);
        $staleProperty2->getName()->willReturn('i18n:fr-title');
        $staleProperty2->remove()->shouldBeCalledOnce();

        $validProperty = $this->prophesize(PropertyInterface::class);
        $validProperty->getName()->willReturn('i18n:de-template');
        $validProperty->remove()->shouldNotBeCalled();

        $unlocalizedProperty = $this->prophesize(PropertyInterface::class);
        $unlocalizedProperty->getName()->willReturn('sulu:order');
        $unlocalizedProperty->remove()->shouldNotBeCalled();

        $node->getProperties()->willReturn([
            $staleProperty1->reveal(),
            $staleProperty2->reveal(),
            $validProperty->reveal(),
            $unlocalizedProperty->reveal(),
        ]);

        $count = $this->command->removeStaleLocaleProperties($node->reveal(), ['fr'], false);

        $this->assertSame(2, $count);
    }

    public function testRemoveStaleLocalePropertiesDryRun(): void
    {
        $node = $this->prophesize(NodeInterface::class);

        $staleProperty = $this->prophesize(PropertyInterface::class);
        $staleProperty->getName()->willReturn('i18n:fr-template');
        $staleProperty->remove()->shouldNotBeCalled();

        $node->getProperties()->willReturn([$staleProperty->reveal()]);

        $count = $this->command->removeStaleLocaleProperties($node->reveal(), ['fr'], true);

        $this->assertSame(1, $count);
    }

    public function testRemoveStaleLocalePropertiesNoStaleLocales(): void
    {
        $node = $this->prophesize(NodeInterface::class);

        $count = $this->command->removeStaleLocaleProperties($node->reveal(), [], false);

        $this->assertSame(0, $count);
    }

    public function testCleanInvalidShadowReferences(): void
    {
        $node = $this->prophesize(NodeInterface::class);

        // i18n:en-shadow-on = true, i18n:en-shadow-base = "fr" (fr no longer exists)
        $shadowOnProp = $this->prophesize(PropertyInterface::class);
        $shadowOnProp->getName()->willReturn('i18n:en-shadow-on');
        $node->hasProperty('i18n:en-shadow-on')->willReturn(true);
        $node->getProperty('i18n:en-shadow-on')->willReturn($shadowOnProp->reveal());
        $shadowOnProp->getValue()->willReturn(true);
        $shadowOnProp->setValue(null)->shouldBeCalledOnce();

        $shadowBaseProp = $this->prophesize(PropertyInterface::class);
        $shadowBaseProp->getName()->willReturn('i18n:en-shadow-base');
        $node->hasProperty('i18n:en-shadow-base')->willReturn(true);
        $node->getProperty('i18n:en-shadow-base')->willReturn($shadowBaseProp->reveal());
        $shadowBaseProp->getValue()->willReturn('fr');
        $shadowBaseProp->setValue(null)->shouldBeCalledOnce();

        // de locale has no shadow properties
        $node->hasProperty('i18n:de-shadow-on')->willReturn(false);

        $result = $this->command->cleanInvalidShadowReferences($node->reveal(), ['en', 'de'], false);

        $this->assertSame(1, $result);
    }

    public function testCleanInvalidShadowReferencesValidShadow(): void
    {
        $node = $this->prophesize(NodeInterface::class);

        $shadowOnProp = $this->prophesize(PropertyInterface::class);
        $shadowOnProp->getName()->willReturn('i18n:en-shadow-on');
        $node->hasProperty('i18n:en-shadow-on')->willReturn(true);
        $node->getProperty('i18n:en-shadow-on')->willReturn($shadowOnProp->reveal());
        $shadowOnProp->getValue()->willReturn(true);
        $shadowOnProp->setValue(null)->shouldNotBeCalled();

        $shadowBaseProp = $this->prophesize(PropertyInterface::class);
        $shadowBaseProp->getName()->willReturn('i18n:en-shadow-base');
        $node->hasProperty('i18n:en-shadow-base')->willReturn(true);
        $node->getProperty('i18n:en-shadow-base')->willReturn($shadowBaseProp->reveal());
        $shadowBaseProp->getValue()->willReturn('de');
        $shadowBaseProp->setValue(null)->shouldNotBeCalled();

        // de locale has no shadow properties
        $node->hasProperty('i18n:de-shadow-on')->willReturn(false);

        $result = $this->command->cleanInvalidShadowReferences($node->reveal(), ['en', 'de'], false);

        $this->assertSame(0, $result);
    }
}
