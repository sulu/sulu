<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Synchronization;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\SynchronizeBehavior;
use Sulu\Component\Content\Document\Syncronization\DocumentRegistrator;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NodeManager;

/**
 * Abbreviations:.
 *
 * - PDM: Publish document manager.
 * - DDM: Default document manager.
 */
class DocumentRegistratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $pdm;

    /**
     * @var DocumentManagerInterface
     */
    private $ddm;

    /**
     * @var DocumentRegistrator
     */
    private $registrator;

    /**
     * @var DocumentInspector
     */
    private $ddmInspector;

    /**
     * @var DocumentRegistry
     */
    private $ddmRegistry;

    /**
     * @var DocumentRegistry
     */
    private $pdmRegistry;

    /**
     * @var NodeManager
     */
    private $pdmNodeManager;

    /**
     * @var NodeManager
     */
    private $ddmNodeManager;

    /**
     * @var NodeInterface
     */
    private $ddmNode;

    /**
     * @var NodeInterface
     */
    private $ddmNode1;

    /**
     * @var NodeInterface
     */
    private $pdmNode;

    /**
     * @var SynchronizeBehavior
     */
    private $document;

    /**
     * @var SynchronizeBehavior
     */
    private $document1;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    public function setUp()
    {
        $this->pdm = $this->prophesize(DocumentManagerInterface::class);
        $this->ddm = $this->prophesize(DocumentManagerInterface::class);

        $this->registrator = new DocumentRegistrator(
            $this->ddm->reveal(),
            $this->pdm->reveal()
        );

        $this->ddmInspector = $this->prophesize(DocumentInspector::class);
        $this->pdmInspector = $this->prophesize(DocumentInspector::class);
        $this->ddmRegistry = $this->prophesize(DocumentRegistry::class);
        $this->pdmRegistry = $this->prophesize(DocumentRegistry::class);
        $this->pdmNodeManager = $this->prophesize(NodeManager::class);
        $this->ddmNodeManager = $this->prophesize(NodeManager::class);
        $this->ddmNode = $this->prophesize(NodeInterface::class);
        $this->ddmNode1 = $this->prophesize(NodeInterface::class);
        $this->ddmNode2 = $this->prophesize(NodeInterface::class);
        $this->pdmNode = $this->prophesize(NodeInterface::class);
        $this->document = $this->prophesize(SynchronizeBehavior::class);
        $this->document1 = $this->prophesize(SynchronizeBehavior::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);

        $this->ddm->getMetadataFactory()->willReturn($this->metadataFactory->reveal());
        $this->ddm->getNodeManager()->willReturn($this->ddmNodeManager->reveal());
        $this->ddm->getRegistry()->willReturn($this->ddmRegistry->reveal());
        $this->ddm->getInspector()->willReturn($this->ddmInspector->reveal());
        $this->pdm->getRegistry()->willReturn($this->pdmRegistry->reveal());
        $this->pdm->getNodeManager()->willReturn($this->pdmNodeManager->reveal());
    }

    /**
     * If an equivilent document does not exist in the PDM then it should return.
     */
    public function testRegisterNewDocumentNotExisting()
    {
        $this->metadataFactory->getMetadataForClass(get_class($this->document->reveal()))
            ->willReturn($this->metadata->reveal());
        $this->metadata->getFieldMappings()->willReturn([]);
        $this->pdmRegistry->hasDocument($this->document->reveal())->willReturn(false);
        $this->ddmInspector->getUuid($this->document->reveal())->willReturn('1234');
        $this->ddmInspector->getPath($this->document->reveal())->willReturn('/path/to');
        $this->pdmNodeManager->has('1234')->willReturn(false);
        $this->pdmNodeManager->has('/path/to')->willReturn(false);
        $this->pdmNodeManager->has('/path')->willReturn(true);

        $this->ddmInspector->getLocale($this->document->reveal())->willReturn('fr');
        $this->pdmRegistry->registerDocument(Argument::cetera())->shouldNotBeCalled();
        $this->pdmNodeManager->find('1234')->willReturn($this->pdmNode->reveal());

        $this->registrator->registerDocumentWithPDM($this->document->reveal());
    }

    /**
     * It should register a document with an existing node in the PDM.
     */
    public function testRegisterNewDocumentUuidExists()
    {
        $this->metadataFactory->getMetadataForClass(get_class($this->document->reveal()))
            ->willReturn($this->metadata->reveal());
        $this->metadata->getFieldMappings()->willReturn([]);
        $this->pdmRegistry->hasDocument($this->document->reveal())->willReturn(false);
        $this->ddmInspector->getUuid($this->document->reveal())->willReturn('1234');
        $this->ddmInspector->getPath($this->document->reveal())->willReturn('/path/to');
        $this->pdmNodeManager->has('1234')->willReturn(true);
        $this->pdmNodeManager->find('1234')->willReturn($this->pdmNode->reveal());

        $this->ddmInspector->getLocale($this->document->reveal())->willReturn('fr');
        $this->pdmRegistry->registerDocument(
            $this->document->reveal(),
            $this->pdmNode->reveal(),
            'fr'
        )->shouldBeCalled();

        $this->registrator->registerDocumentWithPDM($this->document->reveal());
    }

    /**
     * If the PDM already has the document then it should return early.
     */
    public function testRegisterNewDocumentNotHasReturnEarly()
    {
        $this->metadataFactory->getMetadataForClass(get_class($this->document->reveal()))
            ->willReturn($this->metadata->reveal());
        $this->metadata->getFieldMappings()->willReturn([]);
        $this->pdmRegistry->hasDocument($this->document->reveal())->willReturn(true);

        $this->pdmRegistry->registerDocument(Argument::cetera())->shouldNotBeCalled();
        $this->registrator->registerDocumentWithPDM($this->document->reveal());
    }

    /**
     * If the UUID and the path, and parent path do not exist in the PDM
     * then it should recursively create the ancestor nodes from the DDM
     * (retaining the same UUIDs).
     */
    public function testNoneOfUuidPathOrParentPathExist()
    {
        $this->metadataFactory->getMetadataForClass(get_class($this->document->reveal()))
            ->willReturn($this->metadata->reveal());
        $this->metadata->getFieldMappings()->willReturn([]);
        $this->pdmRegistry->hasDocument($this->document->reveal())->willReturn(false);

        $this->ddmInspector->getUuid($this->document->reveal())->willReturn('1234');
        $this->ddmInspector->getPath($this->document->reveal())->willReturn('/path/to/this');

        $this->pdmNodeManager->has('1234')->willReturn(false);
        $this->pdmNodeManager->has('/path/to/this')->willReturn(false);
        $this->pdmNodeManager->has('/path/to')->willReturn(false);

        $this->ddmNodeManager->find('/path')->willReturn($this->ddmNode1->reveal());
        $this->ddmNodeManager->find('/path/to')->willReturn($this->ddmNode2->reveal());
        $this->ddmNode1->getIdentifier()->willReturn(1);
        $this->ddmNode2->getIdentifier()->willReturn(2);

        $this->pdmNodeManager->createPath('/path', 1)->shouldBeCalled();
        $this->pdmNodeManager->createPath('/path/to', 2)->shouldBeCalled();
        $this->pdmNodeManager->save()->shouldBeCalled(); // save() hack, see comment in code.
        $this->pdmRegistry->registerDocument(Argument::cetera())->shouldNotBeCalled();

        $this->registrator->registerDocumentWithPDM($this->document->reveal());
    }

    /**
     * If the UUID does not exist and the path DOES exist, then it should
     * throw an exception.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Publish document manager already has a node
     */
    public function testUuidNotExistPathDoesExist()
    {
        $this->metadataFactory->getMetadataForClass(get_class($this->document->reveal()))
            ->willReturn($this->metadata->reveal());
        $this->metadata->getFieldMappings()->willReturn([]);
        $this->pdmRegistry->hasDocument($this->document->reveal())->willReturn(false);

        $this->ddmInspector->getUuid($this->document->reveal())->willReturn('1234');
        $this->ddmInspector->getPath($this->document->reveal())->willReturn('/path/to/this');

        $this->pdmNodeManager->has('1234')->willReturn(false);
        $this->pdmNodeManager->has('/path/to/this')->willReturn(true);
        $this->pdmNodeManager->find('/path/to/this')->willReturn($this->pdmNode->reveal());
        $this->pdmNode->getIdentifier()->willReturn('1234');

        $this->registrator->registerDocumentWithPDM($this->document->reveal());
    }

    /**
     * It should update (managed) objects that are mapped to the document.
     * It should skip non-managed objects (e.g. \DateTime).
     * It should skip non-object properties.
     */
    public function testRegisterAssociatedDocuments()
    {
        // we only want to test the associations, so just say that the PDM
        // already has our primary document.
        $this->pdmRegistry->hasDocument($this->document->reveal())->willReturn(true);

        $this->metadataFactory->getMetadataForClass(get_class($this->document->reveal()))
            ->willReturn($this->metadata->reveal());
        $this->metadata->getFieldMappings()->willReturn([
            'one' => 1,
            'two' => 2,
            'three' => 3,
        ]);

        $this->metadata->getFieldValue($this->document->reveal(), 'one')
            ->willReturn('i-am-not-an-object');
        $this->metadata->getFieldValue($this->document->reveal(), 'two')
            ->willReturn($dateTime = new \DateTime());
        $this->metadata->getFieldValue($this->document->reveal(), 'three')
            ->willReturn($this->document1->reveal());

        $this->ddmRegistry->hasDocument($dateTime)->willReturn(false);
        $this->ddmRegistry->hasDocument($this->document1->reveal())->willReturn(true);

        // if we check with the PDM registry that document1 exists, then we are
        // already good.
        $this->pdmRegistry->hasDocument($this->document1->reveal())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->registrator->registerDocumentWithPDM($this->document->reveal());
    }

    /**
     * It should register the parent document if the document is implementing
     * the ParentBehavior.
     */
    public function testRegisterParentBehavior()
    {
        $document = $this->prophesize(SynchronizeBehavior::class)
            ->willImplement(ParentBehavior::class);

        // we only want to test the associations, so just say that the PDM
        // already has our primary document.

        $this->metadataFactory->getMetadataForClass(get_class($document->reveal()))
            ->willReturn($this->metadata->reveal());
        $this->metadata->getFieldMappings()->willReturn([]);
        $document->getParent()->willReturn($this->document1->reveal());

        $this->pdmRegistry->hasDocument($document->reveal())
            ->willReturn(true);
        $this->pdmRegistry->hasDocument($this->document1->reveal())
            ->willReturn(true);

        $this->registrator->registerDocumentWithPDM($document->reveal());
    }
}
