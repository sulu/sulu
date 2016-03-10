<?php

namespace Sulu\Component\Content\Tests\Unit\Document;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentManagerRegistry;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Content\Document\SynchronizationManager;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Prophecy\Argument;
use Sulu\Component\Content\Document\Behavior\SynchronizeBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;

/**
 * Abbreviations:
 *
 * - PDM: Publish document manager.
 * - DDM: Default document manager.
 */
class SynchronizationManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var mixed
     */
    private $managerRegistry;

    /**
     * @var mixed
     */
    private $propertyEncoder;

    /**
     * @var mixed
     */
    private $syncManager;

    /**
     * @var mixed
     */
    private $ddm;

    public function setUp()
    {
        $this->managerRegistry = $this->prophesize(DocumentManagerRegistry::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);

        $this->syncManager = new SynchronizationManager(
            $this->managerRegistry->reveal(),
            $this->propertyEncoder->reveal(),
            'live'
        );

        $this->ddm = $this->prophesize(DocumentManagerInterface::class);
        $this->pdm = $this->prophesize(DocumentManagerInterface::class);
        $this->route1 = $this->prophesize(RouteDocument::class)
            ->willImplement(SynchronizeBehavior::class);
        $this->ddmInspector = $this->prophesize(DocumentInspector::class);
        $this->pdmNode = $this->prophesize(NodeInterface::class);
        $this->ddmNode = $this->prophesize(NodeInterface::class);
        $this->pdmNodeManager = $this->prophesize(NodeManager::class);
        $this->pdmDocumentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->ddmDocumentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->document = $this->prophesize(SynchronizeBehavior::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);

        $this->pdm->getNodeManager()->willReturn($this->pdmNodeManager->reveal());
        $this->pdm->getRegistry()->willReturn($this->pdmDocumentRegistry->reveal());
        $this->ddm->getRegistry()->willReturn($this->ddmDocumentRegistry->reveal());
        $this->ddm->getInspector()->willReturn($this->ddmInspector->reveal());
        $this->ddm->getMetadataFactory()->willReturn($this->metadataFactory->reveal());
    }

    /**
     * (synchronize full) It should return early if publish manager and default manager are
     * the same.
     */
    public function testSynchronizeFullPublishAndDefaultManagersAreSame()
    {
        $this->configureScenario([
            'pdm_name' => 'ddm'
        ]);
        $this->pdm->persist(Argument::cetera())->shouldNotBeCalled();

        $this->syncManager->synchronizeFull($this->document->reveal());
    }

    /**
     * (synchronize full) It should get all the routes for the document and synchronize them.
     */
    public function testSynchronizeRoutes()
    {
        // make the document implement the resource segment behavior - which would indicate
        // that it has routes associated with it.
        $this->document->willImplement(ResourceSegmentBehavior::class);

        // TODO: this is a temporary requirement, see note in class.
        $this->document->setResourceSegment(Argument::type('string'))->shouldBeCalled();

        // of the three referrers, one of them is a route.
        $options = $this->configureScenario([
            'document_referrers' => [ new \stdClass(), $this->route1->reveal(), new \stdClass() ],
            'route_synced_managers' => [ 'live '],
        ]);

        // setup the behavior of the route document...
        $this->route1->getSynchronizedManagers()->willReturn($options['route_synchronized_managers']);
        $this->ddmInspector->getUuid($this->route1->reveal())->willReturn($options['uuid']);
        $this->ddmInspector->getPath($this->route1->reveal())->willReturn($options['path']);
        $this->ddmInspector->getNode($this->route1->reveal())->willReturn($this->ddmNode->reveal());
        $this->ddmInspector->getLocale($this->route1->reveal())->willReturn($options['locale']);
        $this->pdmDocumentRegistry->hasDocument($this->route1->reveal())->willReturn(true);
        $this->metadataFactory->getMetadataForClass(get_class($this->route1->reveal()))->willReturn($this->metadata->reveal());

        // setup the expectations
        $this->pdm->persist($this->document->reveal(), Argument::cetera())->shouldBeCalled();
        $this->pdm->persist($this->route1->reveal(), Argument::cetera())->shouldBeCalled();
        $this->pdm->flush()->shouldBeCalled();
        $this->ddm->flush()->shouldBeCalled();

        $this->syncManager->synchronizeFull($this->document->reveal());
    }

    /**
     * It should return early if the default and publish manager are the same.
     */
    public function testSameDefaultAndPublishManagers()
    {
        $this->configureScenario([
            'pdm_name' => 'ddm'
        ]);

        $this->pdm->persist(Argument::cetera())->shouldNotBeCalled();

        $this->syncManager->synchronizeSingle($this->document->reveal());
    }

    /**
     * It should localize the PHPCR property if the document is localized.
     */
    public function testLocalizedPhpcrSyncedProperty()
    {
        $this->document->willImplement(LocaleBehavior::class);
        $options = $this->configureScenario([
            'localized' => true,
        ]);
        $this->pdm->persist(Argument::cetera())->shouldBeCalled();
        $this->ddmNode->setProperty($options['synced_property_name'], ['live'])->shouldBeCalled();
        $this->propertyEncoder->localizedSystemName($options['synced_property_name'], $options['locale'])->shouldBeCalled();
        $this->syncManager->synchronizeSingle($this->document->reveal());
    }

    /**
     * It should not synchronize if force is false and the document believes that it is
     * already synchronized.
     */
    public function testDocumentBelievesItIsSynchronizedNoForce()
    {
        $this->configureScenario([
            'document_synchronized_managers' => [ 'live' ],
        ]);

        $this->pdm->persist(Argument::cetera())->shouldNotBeCalled();

        $this->syncManager->synchronizeSingle($this->document->reveal());
    }

    /**
     * If the PDM already has the incoming PHP node, then the document from the
     * DDM should be registered against the PDM PHPCR node.
     */
    public function testNodeAlreadyInPDM()
    {
        $options = $this->configureScenario([
            'pdm_registry_has_document' => false,
            'pdm_node_manager_has_node' => true,
        ]);

        $this->pdmDocumentRegistry->registerDocument(
            $this->document->reveal(),
            $this->pdmNode->reveal(),
            $options['locale']
        )->shouldBeCalled();

        $this->pdm->persist(Argument::cetera())->shouldBeCalled();

        $this->syncManager->synchronizeSingle($this->document->reveal());
    }

    /**
     * If the document has a parent behavior, then we should remove the proxy.
     */
    public function testDocumentHasParentBehavior()
    {
        $parent = new \stdClass();
        $this->document->willImplement(ParentBehavior::class);

        $this->configureScenario([]);

        $this->document->getParent()->willReturn($parent);
        $this->pdm->persist(Argument::cetera())->shouldBeCalled();

        $this->syncManager->synchronizeSingle($this->document->reveal());
    }

    /**
     * It should synchronize a document to the publish document manager.
     * It should register the fact that the document is synchronized with the PDM.
     * It should NOT localize the PHPCR property for a non-localized document.
     */
    public function testPublishSingle()
    {
        $options = $this->configureScenario([]);

        $this->pdm->persist(
            $this->document->reveal(),
            $options['locale'],
            [
                'path' => $options['path'],
                'auto_create' => true,
            ]
        )->shouldBeCalled();

        $this->ddmNode->setProperty($options['synced_property_name'], [ 'live' ])->shouldBeCalled();
        $this->propertyEncoder->systemName($options['synced_property_name'], $options['locale'])->shouldBeCalled();

        $this->syncManager->synchronizeSingle($this->document->reveal());
    }

    /**
     * It should 

    /**
     * Utility method to setup the standard requirements for this test.
     */
    private function configureScenario(array $options)
    {
        $options = array_merge(array(
            'ddm_name' => 'ddm',
            'pdm_name' => 'pdm',
            'document_synchronized_managers' => [],
            'route_synchronized_managers' => [],
            'uuid' => '1234',
            'locale' => 'de',
            'path' => '/',
            'pdm_node_manager_has_node' => false,
            'pdm_registry_has_document' => true,
            'synced_property_name' => 'synced',
            'document_field_mappings' => [],
            'document_referrers' => [],
            'localized' => false,
        ), $options);

        $this->managerRegistry->getManager()->willReturn($this->{$options['ddm_name']}->reveal());
        $this->managerRegistry->getManager('live')->willReturn($this->{$options['pdm_name']}->reveal());

        $this->document->getSynchronizedManagers()->willReturn($options['document_synchronized_managers']);

        $this->ddmInspector->getUuid($this->document->reveal())->willReturn($options['uuid']);
        $this->ddmInspector->getPath($this->document->reveal())->willReturn($options['path']);
        $this->ddmInspector->getNode($this->document->reveal())->willReturn($this->ddmNode->reveal());
        $this->ddmInspector->getReferrers($this->document->reveal())->willReturn($options['document_referrers']);
        $this->ddmInspector->getLocale($this->document->reveal())->willReturn($options['locale']);

        $this->pdmNodeManager->find($options['uuid'])->willReturn($this->pdmNode->reveal());
        $this->pdmNodeManager->has($options['uuid'])->willReturn(true);
        $this->pdmDocumentRegistry->hasDocument($this->document->reveal())->willReturn($options['pdm_registry_has_document']);

        if ($options['localized']) {
            $this->propertyEncoder->localizedSystemName(
                SynchronizeBehavior::SYNCED_FIELD,
                $options['locale']
            )->willReturn($options['synced_property_name']);
        } else {
            $this->propertyEncoder->systemName(
                SynchronizeBehavior::SYNCED_FIELD,
                $options['locale']
            )->willReturn($options['synced_property_name']);
        }
        $this->metadataFactory->getMetadataForClass(get_class($this->document->reveal()))->willReturn($this->metadata->reveal());
        $this->metadata->getFieldMappings()->willReturn($options['document_field_mappings']);
        $this->metadata->getReflectionClass()->willReturn(new \ReflectionClass(get_class($this->document->reveal())));

        return $options;
    }
}
