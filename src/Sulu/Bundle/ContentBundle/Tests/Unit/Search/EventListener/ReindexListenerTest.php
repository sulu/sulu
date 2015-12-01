<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventListener;

use Massive\Bundle\SearchBundle\Search\Event\IndexRebuildEvent;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Metadata\BaseMetadataFactory;
use Sulu\Component\DocumentManager\Query\Query;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var BaseMetadataFactory
     */
    private $baseMetadataFactory;

    /**
     * @var array
     */
    private $mapping = [];

    /**
     * @var ReindexListener
     */
    private $reindexListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);
        $this->baseMetadataFactory = $this->prophesize(BaseMetadataFactory::class);

        $this->reindexListener = new ReindexListener(
            $this->documentManager->reveal(),
            $this->inspector->reveal(),
            $this->searchManager->reveal(),
            $this->baseMetadataFactory->reveal(),
            $this->mapping
        );
    }

    public function testOnIndexRebuild()
    {
        $event = $this->prophesize(IndexRebuildEvent::class);
        $query = $this->prophesize(Query::class);
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(UuidBehavior::class);
        $securableDocument = $this->prophesize(StructureBehavior::class);
        $securableDocument->willImplement(SecurityBehavior::class);
        $securableDocument->willImplement(UuidBehavior::class);
        $securableDocument->getPermissions()->willReturn([]);
        $securedDocument = $this->prophesize(StructureBehavior::class);
        $securedDocument->willImplement(SecurityBehavior::class);
        $securedDocument->willImplement(UuidBehavior::class);
        $securedDocument->getPermissions()->willReturn(['some' => 'permissions']);

        $typemap = [
            ['phpcr_type' => 'page'],
            ['phpcr_type' => 'home'],
            ['phpcr_type' => 'snippet'],
        ];

        $output = $this->prophesize(OutputInterface::class);
        $event->getOutput()->willReturn($output->reveal());
        $event->getFilter()->shouldBeCalled();

        $this->baseMetadataFactory->getPhpcrTypeMap()->shouldBeCalled()->willReturn($typemap);

        $this->documentManager->createQuery(
            'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "page" or [jcr:mixinTypes] = "home" or [jcr:mixinTypes] = "snippet"'
        )->shouldBeCalled()->willReturn($query->reveal());

        $query->execute()->shouldBeCalled()->willReturn(
            [$document->reveal(), $securableDocument->reveal(), $securedDocument->reveal()]
        );

        $this->inspector->getLocales($document->reveal())->shouldBeCalled()->willReturn(['de', 'en']);
        $document->getUuid()->shouldBeCalled()->willReturn('1');
        $this->documentManager->find('1', 'en')->shouldBeCalled();
        $this->searchManager->index($document->reveal(), 'en')->shouldBeCalled();
        $this->documentManager->find('1', 'de')->shouldBeCalled();
        $this->searchManager->index($document->reveal(), 'de')->shouldBeCalled();

        $this->inspector->getLocales($securableDocument->reveal())->shouldBeCalled()->willReturn(['de']);
        $securableDocument->getUuid()->willReturn('2');
        $this->documentManager->find('2', 'de')->shouldBeCalled();
        $this->searchManager->index($securableDocument->reveal(), 'de')->shouldBeCalled();

        $securedDocument->getUuid()->willReturn('3');
        $this->documentManager->find('3', 'de')->shouldNotBeCalled();
        $this->searchManager->index($securedDocument->reveal(), 'de')->shouldNotBeCalled();

        $this->reindexListener->onIndexRebuild($event->reveal());
    }
}
