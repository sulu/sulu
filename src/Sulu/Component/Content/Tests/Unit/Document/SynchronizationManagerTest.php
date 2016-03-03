<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentManagerRegistry;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\SynchronizeBehavior;
use Sulu\Component\Content\Document\SynchronizationManager;
use Sulu\Component\Content\Document\Syncronization\DocumentRegistrator;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * Abbreviations:.
 *
 * - PDM: Publish document manager.
 * - DDM: Default document manager.
 */
class SynchronizationManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var SynchronizationManager
     */
    private $syncManager;

    /**
     * @var DocumentManagerInterface
     */
    private $ddm;

    /**
     * @var DocumentRegistrator
     */
    private $registrator;

    /**
     * @var DocumentManagerInterface
     */
    private $pdm;

    /**
     * @var DocumentInspector
     */
    private $ddmInspector;

    /**
     * @var NodeInterface
     */
    private $ddmNode;

    /**
     * @var SynchronizeBehavior
     */
    private $document;

    public function setUp()
    {
        $this->managerRegistry = $this->prophesize(DocumentManagerRegistry::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->registrator = $this->prophesize(DocumentRegistrator::class);

        $this->syncManager = new SynchronizationManager(
            $this->managerRegistry->reveal(),
            $this->propertyEncoder->reveal(),
            'live',
            $this->registrator->reveal()
        );

        $this->ddm = $this->prophesize(DocumentManagerInterface::class);
        $this->pdm = $this->prophesize(DocumentManagerInterface::class);
        $this->route1 = $this->prophesize(RouteDocument::class)
            ->willImplement(SynchronizeBehavior::class);
        $this->ddmInspector = $this->prophesize(DocumentInspector::class);
        $this->ddmNode = $this->prophesize(NodeInterface::class);
        $this->document = $this->prophesize(SynchronizeBehavior::class);

        $this->ddm->getInspector()->willReturn($this->ddmInspector->reveal());
    }

    /**
     * (synchronize full) It should return early if publish manager and default manager are
     * the same.
     */
    public function testSynchronizeFullPublishAndDefaultManagersAreSame()
    {
        $this->managerRegistry->getManager()->willReturn($this->ddm->reveal());
        $this->managerRegistry->getManager('live')->willReturn($this->ddm->reveal());

        $this->pdm->persist(Argument::cetera())->shouldNotBeCalled();

        $this->syncManager->synchronizeFull($this->document->reveal());
    }

    /**
     * (synchronize full) It should get all the routes for the document and synchronize them.
     */
    public function testSynchronizeRoutes()
    {
        $this->managerRegistry->getManager()->willReturn($this->ddm->reveal());
        $this->managerRegistry->getManager('live')->willReturn($this->pdm->reveal());

        // make the document implement the resource segment behavior - which would indicate
        // that it has routes associated with it.
        $this->document->willImplement(ResourceSegmentBehavior::class);

        // return one route and one stdClass (the stdClass should be filtered)
        $this->ddmInspector->getReferrers($this->document->reveal())->willReturn([
            $this->route1->reveal(),
            new \stdClass(),
        ]);

        // the route is not currently synced
        $this->route1->getSynchronizedManagers()->willReturn([]);

        // the main document IS already synced (somehow)
        // so it will not be re-persisted to the PDM
        $this->document->getSynchronizedManagers()->willReturn(['live']);

        $this->ddmInspector->getLocale($this->route1->reveal())->willReturn('fr');
        $this->ddmInspector->getPath($this->route1->reveal())->willReturn('/path/1');
        $this->ddmInspector->getNode($this->route1->reveal())->willReturn($this->ddmNode->reveal());

        // persist should be called once for the route document
        $this->pdm->persist(
            $this->route1->reveal(),
            'fr',
            [
                'path' => '/path/1',
            ]
        )->shouldBeCalled();

        // both the PDM and the DDM should be flushed.
        $this->pdm->flush()->shouldBeCalledTimes(1);
        $this->ddm->flush()->shouldBeCalledTimes(1);

        $this->syncManager->synchronizeFull($this->document->reveal());
    }

    /**
     * It should return early if the default and publish manager are the same.
     */
    public function testSameDefaultAndPublishManagers()
    {
        $this->managerRegistry->getManager()->willReturn($this->ddm->reveal());
        $this->managerRegistry->getManager('live')->willReturn($this->ddm->reveal());

        $this->pdm->persist(Argument::cetera())->shouldNotBeCalled();

        $this->syncManager->synchronizeSingle($this->document->reveal());
    }

    /**
     * It should localize the PHPCR property if the document is localized.
     */
    public function testLocalizedPhpcrSyncedProperty()
    {
        $this->managerRegistry->getManager()->willReturn($this->ddm->reveal());
        $this->managerRegistry->getManager('live')->willReturn($this->pdm->reveal());

        $this->document->willImplement(LocaleBehavior::class);
        $this->document->getSynchronizedManagers()->willReturn([]);

        $this->ddmInspector->getLocale($this->document->reveal())->willReturn('fr');
        $this->ddmInspector->getPath($this->document->reveal())->willReturn('/path/1');
        $this->ddmInspector->getNode($this->document->reveal())->willReturn($this->ddmNode->reveal());

        $this->pdm->persist(Argument::cetera())->shouldBeCalled();
        $this->propertyEncoder->localizedSystemName(
            SynchronizeBehavior::SYNCED_FIELD,
            'fr'
        )->willReturn('foobar');
        $this->ddmNode->setProperty('foobar', ['live'])->shouldBeCalled();

        $this->syncManager->synchronizeSingle($this->document->reveal());
    }

    /**
     * It should not synchronize if force is false and the document believes that it is
     * already synchronized.
     */
    public function testDocumentBelievesItIsSynchronizedNoForce()
    {
        $this->managerRegistry->getManager()->willReturn($this->ddm->reveal());
        $this->managerRegistry->getManager('live')->willReturn($this->pdm->reveal());
        $this->document->getSynchronizedManagers()->willReturn(['live']);

        $this->pdm->persist(Argument::cetera())->shouldNotBeCalled();

        $this->syncManager->synchronizeSingle($this->document->reveal());
    }

    /**
     * It should synchronize a document to the publish document manager.
     * It should register the fact that the document is synchronized with the PDM.
     * It should NOT localize the PHPCR property for a non-localized document.
     */
    public function testPublishSingle()
    {
        $this->managerRegistry->getManager()->willReturn($this->ddm->reveal());
        $this->managerRegistry->getManager('live')->willReturn($this->pdm->reveal());
        $this->document->getSynchronizedManagers()->willReturn([]);

        $this->ddmInspector->getLocale($this->document->reveal())->willReturn('fr');
        $this->ddmInspector->getPath($this->document->reveal())->willReturn('/path/1');
        $this->ddmInspector->getNode($this->document->reveal())->willReturn($this->ddmNode->reveal());

        $this->propertyEncoder->systemName(SynchronizeBehavior::SYNCED_FIELD)->shouldBeCalled();
        $this->pdm->persist(
            $this->document->reveal(),
            'fr',
            [
                'path' => '/path/1',
            ]
        )->shouldBeCalled();

        $this->syncManager->synchronizeSingle($this->document->reveal());
    }
}
