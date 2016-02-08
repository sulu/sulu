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
use Massive\Bundle\SearchBundle\Search\ReIndex\ResumeManager;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Metadata\BaseMetadataFactory;
use Sulu\Component\DocumentManager\Query\Query;
use Symfony\Component\Console\Output\BufferedOutput;

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
     * @var ResumeManager
     */
    private $resumeManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);
        $this->baseMetadataFactory = $this->prophesize(BaseMetadataFactory::class);
        $this->resumeManager = $this->prophesize(ResumeManager::class);

        $this->reindexListener = new ReindexListener(
            $this->documentManager->reveal(),
            $this->inspector->reveal(),
            $this->searchManager->reveal(),
            $this->baseMetadataFactory->reveal(),
            $this->resumeManager->reveal(),
            $this->mapping
        );

        $this->output = new BufferedOutput();
    }

    /**
     * It should index documents.
     */
    public function testIndexDocuments()
    {
        $this->configureListener([
            'documents' => [
                '1' => [
                    'behaviors' => [UuidBehavior::class],
                ],
                '2' => [
                    'behaviors' => [UuidBehavior::class],
                ],
            ],
            'documents_to_index' => [1, 2],
        ]);

        $this->reindexListener->onIndexRebuild($this->event->reveal());
    }

    /**
     * It should skip documents with permissions.
     */
    public function testSkipDocumensWithPermisions()
    {
        $this->configureListener([
            'documents' => [
                '1' => [
                    'behaviors' => [UuidBehavior::class],
                ],
                '2' => [
                    'behaviors' => [UuidBehavior::class, SecurityBehavior::class],
                    'permissions' => [],
                ],
                '3' => [
                    'behaviors' => [UuidBehavior::class, SecurityBehavior::class],
                    'permissions' => ['one', 'two'],
                ],
                '4' => [
                    'behaviors' => [UuidBehavior::class],
                    'permissions' => null,
                ],
            ],
            'documents_to_index' => [1, 2, 4],
        ]);

        $this->reindexListener->onIndexRebuild($this->event->reveal());
    }

    /**
     * It should exclude document classes which do not match the filter string.
     */
    public function testFilterDocumentsNotMatch()
    {
        $this->configureListener([
            'documents' => [
                '1' => [
                    'behaviors' => [UuidBehavior::class],
                ],
            ],
            'documents_to_index' => [],
            'filter' => 'IAmNotAStructure',
        ]);

        $this->reindexListener->onIndexRebuild($this->event->reveal());
    }

    /**
     * It should include document classes which match the filter string.
     */
    public function testFilterDocuments()
    {
        $this->configureListener([
            'documents' => [
                '1' => [
                    'behaviors' => [UuidBehavior::class],
                ],
            ],
            'documents_to_index' => [1],
            'filter' => 'Structure',
        ]);

        $this->reindexListener->onIndexRebuild($this->event->reveal());
    }

    /**
     * It should show the title for structures implementing the title interface.
     */
    public function testShowTitle()
    {
        $this->configureListener([
            'documents' => [
                '1' => [
                    'behaviors' => [UuidBehavior::class, TitleBehavior::class],
                    'title' => 'Hello World',
                ],
            ],
            'documents_to_index' => [1],
            'filter' => 'Structure',
        ]);

        $this->reindexListener->onIndexRebuild($this->event->reveal());
        $output = $this->output->fetch();
        $this->assertRegExp('/Hello World/', $output);
    }

    /**
     * It should show the OID for structures not implementing the TitleBehavior.
     */
    public function testShowOud()
    {
        $this->configureListener([
            'documents' => [
                '1' => [
                    'behaviors' => [UuidBehavior::class],
                ],
            ],
            'documents_to_index' => [1],
        ]);

        $this->reindexListener->onIndexRebuild($this->event->reveal());
        $output = $this->output->fetch();
        $this->assertRegExp('/OID: /', $output);
    }

    /**
     * It should log an error if getting locales throws an exception.
     */
    public function testLogErrorLocales()
    {
        $this->configureListener([
            'documents' => [
                '1' => [
                    'behaviors' => [UuidBehavior::class],
                ],
            ],
            'documents_to_index' => [1],
            'locales_exception' => 'Foobar',
        ]);

        $this->reindexListener->onIndexRebuild($this->event->reveal());
        $output = $this->output->fetch();
        $this->assertRegExp('/Error indexing page/', $output);
    }

    /**
     * It should log an error if exception encountered when indexing.
     */
    public function testLogErrorIndexing()
    {
        $this->configureListener([
            'documents' => [
                '1' => [
                    'behaviors' => [UuidBehavior::class],
                ],
            ],
            'documents_to_index' => [1],
            'index_exception' => 'Foobar',
        ]);

        $this->reindexListener->onIndexRebuild($this->event->reveal());
        $output = $this->output->fetch();
        $this->assertRegExp('/Error indexing locale/', $output);
    }

    private function configureListener(array $options)
    {
        $options = array_merge([
            'documents' => [],
            'documents_to_index' => [],
            'locales' => ['de', 'en'],
            'filter' => null,
            'locales_exception' => null,
            'index_exception' => null,
        ], $options);

        $this->event = $this->prophesize(IndexRebuildEvent::class);
        $this->event->getOutput()->willReturn($this->output);
        $this->event->getFilter()->willReturn($options['filter']);

        $documents = [];

        foreach ($options['documents'] as $uuid => $documentOptions) {
            $documentOptions = array_merge([
                'behaviors' => [UuidBehavior::class],
                'permissions' => null,
                'title' => null,
            ], $documentOptions);

            $document = $this->prophesize(StructureBehavior::class);
            foreach ($documentOptions['behaviors'] as $behavior) {
                $document->willImplement($behavior);
            }

            $document->getUuid()->willReturn($uuid);

            if (null !== $documentOptions['permissions']) {
                $document->getPermissions()->willReturn($documentOptions['permissions']);
            }

            if (null !== $documentOptions['title']) {
                $document->getTitle()->willReturn($documentOptions['title']);
            }

            $documents[$uuid] = $document->reveal();
        }

        $typemap = [
            ['phpcr_type' => 'page'],
            ['phpcr_type' => 'home'],
            ['phpcr_type' => 'snippet'],
        ];
        $this->baseMetadataFactory->getPhpcrTypeMap()->shouldBeCalled()->willReturn($typemap);

        $query = $this->prophesize(Query::class);
        $this->documentManager->createQuery(Argument::type('string'))
            ->shouldBeCalled()->willReturn($query->reveal());
        $this->resumeManager->getCheckpoint(ReindexListener::CHECKPOINT_NAME, 0)->willReturn(0);

        $query->setFirstResult(0)->shouldBeCalled();
        $query->setFirstResult(50)->shouldBeCalled();
        $query->setMaxResults(50)->shouldBeCalled();

        $query->execute()->shouldBeCalled()->willReturn(
            new \ArrayIterator($documents),
            new \ArrayIterator([])
        );
        $this->documentManager->clear()->shouldBeCalled();
        $this->resumeManager->setCheckpoint(ReindexListener::CHECKPOINT_NAME, 50)->shouldBeCalled();

        foreach ($documents as $uuid => $document) {
            if ($options['locales_exception']) {
                $this->inspector->getLocales()->willThrow(new \Exception($options['locales_exception']));
                continue;
            }

            $this->inspector->getLocales($document)->willReturn($options['locales']);

            foreach ($options['locales'] as $locale) {
                if (in_array($uuid, $options['documents_to_index'])) {
                    $this->documentManager->find($uuid, $locale)->shouldBeCalled();

                    if ($options['index_exception']) {
                        $this->searchManager->index($document, $locale)->willThrow(new \Exception($options['index_exception']));
                        continue;
                    }

                    $this->searchManager->index($document, $locale)->shouldBeCalled();
                    continue;
                }

                $this->documentManager->find($uuid, $locale)->shouldNotBeCalled();
                $this->searchManager->index($document, $locale)->shouldNotBeCalled();
            }
        }

        $this->resumeManager->removeCheckpoint(ReindexListener::CHECKPOINT_NAME)->shouldBeCalled();
    }
}
