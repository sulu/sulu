<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventListener;

use Massive\Bundle\SearchBundle\Search\Event\IndexRebuildEvent;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Console\Helper\ProgressHelper;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;

/**
 * Listen to for new hits. If document instance of structure
 * prefix the current resource locator prefix to the URL.
 */
class ReindexListener
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var string
     */
    private $mapping;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var StructureMetadataFactory
     */
    private $sturctureFactory;

    public function __construct(
        DocumentManager $documentManager,
        DocumentInspector $inspector,
        SearchManagerInterface $searchManager,
        MetadataFactoryInterface $metadataFactory,
        StructureMetadataFactory $structureFactory,
        array $mapping = array()
    ) {
        $this->searchManager = $searchManager;
        $this->mapping = $mapping;
        $this->documentManager = $documentManager;
        $this->inspector = $inspector;
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
    }

    /**
     * Prefix url of document with current resourcelocator prefix.
     *
     * @param IndexRebuildEvent $event
     */
    public function onIndexRebuild(IndexRebuildEvent $event)
    {
        $output = $event->getOutput();
        $purge = $event->getPurge();
        $filter = $event->getFilter();

        $count = array();

        if ($purge) {
            $this->purgeContentIndexes($output);
        }

        foreach ($this->metadataFactory->getAliases() as $documentAlias) {
            if (!$this->structureFactory->hasStructuresFor($documentAlias)) {
                continue;
            }

            $output->writeln(sprintf('<comment>Rebuilding structures for document alias "</comment>%s<comment>"</comment>', $documentAlias));
            $query = $this->documentManager->createQueryBuilder();
            $query->from()->document($documentAlias, 'd');
            $documents = $query->getQuery()->execute();
            if (!count($documents)) {
                $output->writeln(' >> No documents found');
                $output->writeln('');
                continue;
            }
            $progress = new ProgressHelper();
            $progress->start($output, count($documents));

            $this->buildIndex($output, $progress, $documents, $filter);
            $output->writeln('');
            $output->writeln('');
        }

        foreach ($count as $className => $count) {
            if ($count == 0) {
                continue;
            }

            $output->writeln(sprintf(
                '<comment>Content</comment>: %s <info>%s</info> indexed',
                $className,
                $count
            ));
        }
    }

    private function buildIndex(OutputInterface $output, ProgressHelper $progress, $documents, $filter)
    {
        foreach ($documents as $document) {
            $locales = $this->inspector->getLocales($document);

            foreach ($locales as $locale) {
                try {
                    $this->documentManager->find($document->getUuid(), $locale);
                    $documentClass = get_class($document);

                    if ($filter && !preg_match('{' . $filter . '}', $documentClass)) {
                        continue;
                    }

                    $this->searchManager->index($document, $locale);
                    if (!isset($count[$documentClass])) {
                        $count[$documentClass] = 0;
                    }
                    $count[$documentClass]++;
                } catch (\Exception $e) {
                    $output->writeln(sprintf(
                        '<error>Error indexing or de-indexing page (path: %s locale: %s)</error>: %s',
                        $this->inspector->getPath($document),
                        $locale,
                        $e->getMessage()
                    ));
                }
            }

            $progress->advance();
        }
    }

    private function purgeContentIndexes($output)
    {
        foreach ($this->mapping as $structureMapping) {
            $structureIndexName = $structureMapping['index'];
            $output->writeln('<comment>Purging index</comment>: ' . $structureIndexName);
            $this->searchManager->purge($structureIndexName);
        }
    }
}
