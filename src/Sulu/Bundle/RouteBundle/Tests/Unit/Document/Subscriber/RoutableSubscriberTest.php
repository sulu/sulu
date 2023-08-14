<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\Document\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\RouteBundle\Document\Subscriber\RoutableSubscriber;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\Route\Document\Behavior\RoutableBehavior;

class RoutableSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ChainRouteGeneratorInterface>
     */
    private $chainRouteGenerator;

    /**
     * @var ObjectProphecy<RouteManagerInterface>
     */
    private $routeManager;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private $routeRepository;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $documentInspector;

    /**
     * @var ObjectProphecy<PropertyEncoder>
     */
    private $propertyEncoder;

    /**
     * @var ObjectProphecy<StructureMetadataFactoryInterface>
     */
    private $metadataFactory;

    /**
     * @var ObjectProphecy<ConflictResolverInterface>
     */
    private $conflictResolver;

    /**
     * @var RoutableSubscriber
     */
    private $routableSubscriber;

    public function setUp(): void
    {
        $this->chainRouteGenerator = $this->prophesize(ChainRouteGeneratorInterface::class);
        $this->routeManager = $this->prophesize(RouteManagerInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->metadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->conflictResolver = $this->prophesize(ConflictResolverInterface::class);

        $this->routableSubscriber = new RoutableSubscriber(
            $this->chainRouteGenerator->reveal(),
            $this->routeManager->reveal(),
            $this->routeRepository->reveal(),
            $this->entityManager->reveal(),
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->propertyEncoder->reveal(),
            $this->metadataFactory->reveal(),
            $this->conflictResolver->reveal()
        );
    }

    public function testHandleCopy(): void
    {
        $document = $this->prophesize(RoutableBehavior::class);

        $copyEvent = new CopyEvent(
            $document->reveal(),
            'destId'
        );

        $this->documentInspector->getLocales($document->reveal())
            ->willReturn(['en', 'de'])
            ->shouldBeCalled();

        $localizedDocumentEn = $this->prophesize(RoutableBehavior::class);
        $localizedDocumentDe = $this->prophesize(RoutableBehavior::class);

        $this->documentManager->find($copyEvent->getCopiedPath(), 'en')
            ->willReturn($localizedDocumentEn->reveal())
            ->shouldBeCalled();

        $this->documentManager->find($copyEvent->getCopiedPath(), 'de')
            ->willReturn($localizedDocumentDe->reveal())
            ->shouldBeCalled();

        $localizedDocumentEn->getRoutePath() // this is required to be called that handle copy works correctly live generated urls
            ->willReturn('/test-en')
            ->shouldBeCalled();

        $localizedDocumentDe->getRoutePath() // this is required to be called that handle copy works correctly live generated urls
            ->willReturn('/test-de')
            ->shouldBeCalled();

        $routeEn = $this->prophesize(RouteInterface::class);
        $routeEn->getPath()
            ->willReturn('/test-en-2')
            ->shouldBeCalled();

        $routeDe = $this->prophesize(RouteInterface::class);
        $routeDe->getPath()
            ->willReturn('/test-de-2')
            ->shouldBeCalled();

        $this->conflictResolver->resolve($routeDe->reveal())
            ->willReturn($routeDe->reveal())
            ->shouldBeCalled();

        $this->conflictResolver->resolve($routeEn->reveal())
            ->willReturn($routeEn->reveal())
            ->shouldBeCalled();

        $this->chainRouteGenerator->generate($localizedDocumentEn, '/test-en')
            ->willReturn($routeEn->reveal())
            ->shouldBeCalled();

        $this->chainRouteGenerator->generate($localizedDocumentDe, '/test-de')
            ->willReturn($routeDe->reveal())
            ->shouldBeCalled();

        $nodeEn = $this->prophesize(NodeInterface::class);
        $nodeEn->setProperty('i18n:en-routePath', '/test-en-2')
            ->shouldBeCalled();

        $nodeDe = $this->prophesize(NodeInterface::class);
        $nodeDe->setProperty('i18n:de-routePath', '/test-de-2')
            ->willReturn()
            ->shouldBeCalled();

        $node = $this->documentInspector->getNode($localizedDocumentEn)
            ->willReturn($nodeEn)
            ->shouldBeCalled();

        $node = $this->documentInspector->getNode($localizedDocumentDe)
            ->willReturn($nodeDe)
            ->shouldBeCalled();

        $this->documentInspector->getStructureMetadata(Argument::cetera())
            ->shouldBeCalled();

        $this->propertyEncoder->localizedSystemName(Argument::any(), 'en')
            ->willReturn('i18n:en-routePath')
            ->shouldBeCalled();

        $this->propertyEncoder->localizedSystemName(Argument::any(), 'de')
            ->willReturn('i18n:de-routePath')
            ->shouldBeCalled();

        $localizedDocumentEn->setRoutePath('/test-en-2')
            ->shouldBeCalled();

        $localizedDocumentDe->setRoutePath('/test-de-2')
            ->shouldBeCalled();

        $this->routableSubscriber->handleCopy($copyEvent);
    }
}
