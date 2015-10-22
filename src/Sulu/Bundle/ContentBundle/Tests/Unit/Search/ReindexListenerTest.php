<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Search;

use Massive\Bundle\SearchBundle\Search\Event\IndexRebuildEvent;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Bundle\ContentBundle\Search\EventListener\ReindexListener;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
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

        $typemap = [
            ['phpcr_type' => 'page'],
            ['phpcr_type' => 'home'],
            ['phpcr_type' => 'snippet'],
        ];

        $output = $this->prophesize(OutputInterface::class);
        $event->getOutput()->willReturn($output);
        $event->getPurge()->shouldBeCalled();
        $event->getFilter()->shouldBeCalled();

        $this->baseMetadataFactory->getPhpcrTypeMap()->shouldBeCalled()->willReturn($typemap);

        $this->documentManager->createQuery(
            'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "page" or [jcr:mixinTypes] = "home" or [jcr:mixinTypes] = "snippet"'
        )->shouldBeCalled()->willReturn($query->reveal());

        $query->execute()->shouldBeCalled()->willReturn([$document->reveal(), $document->reveal()]);

        $this->inspector->getLocales($document->reveal())->shouldBeCalled()->willReturn(['de', 'en']);

        $document->getUuid()->shouldBeCalled()->willReturn('abcde-abdce-abcde');

        $this->documentManager->find('abcde-abdce-abcde', 'en')->shouldBeCalled();
        $this->searchManager->index($document->reveal(), 'en')->shouldBeCalled();

        $this->documentManager->find('abcde-abdce-abcde', 'de')->shouldBeCalled();
        $this->searchManager->index($document->reveal(), 'de')->shouldBeCalled();

        $this->reindexListener->onIndexRebuild($event->reveal());
    }
}
