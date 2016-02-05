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
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Metadata\BaseMetadataFactory;
use Symfony\Component\Console\Helper\ProgressHelper;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Massive\Bundle\SearchBundle\Search\Reindex\ResumeManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Listen to for new hits. If document instance of structure
 * prefix the current resource locator prefix to the URL.
 */
class ReindexListener
{
    const CHECKPOINT_NAME = 'Sulu Structure';

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
     * @var BaseMetadataFactory
     */
    private $baseMetadataFactory;

    /**
     * @var RebuildResumeManager
     */
    private $resumeManager;

    public function __construct(
        DocumentManager $documentManager,
        DocumentInspector $inspector,
        SearchManagerInterface $searchManager,
        BaseMetadataFactory $baseMetadataFactory,
        ResumeManager $resumeManager,
        array $mapping = []
    ) {
        $this->searchManager = $searchManager;
        $this->mapping = $mapping;
        $this->documentManager = $documentManager;
        $this->inspector = $inspector;
        $this->baseMetadataFactory = $baseMetadataFactory;
        $this->resumeManager = $resumeManager;
    }

    /**
     * Prefix url of document with current resourcelocator prefix.
     *
     * @param IndexRebuildEvent $event
     */
    public function onIndexRebuild(IndexRebuildEvent $event)
    {
        $this->doIndexRebuild(
            $event->getOutput(),
            $event->getFilter()
        );
    }

    private function doIndexRebuild($output, $filter)
    {
        $output->writeln('<info>Rebuilding content index</info>');

        $typeMap = $this->baseMetadataFactory->getPhpcrTypeMap();

        $phpcrTypes = [];
        foreach ($typeMap as $type) {
            $phpcrType = $type['phpcr_type'];

            if ($phpcrType !== 'sulu:path') {
                $phpcrTypes[] = sprintf('[jcr:mixinTypes] = "%s"', $phpcrType);
            }
        }

        $condition = implode(' or ', $phpcrTypes);

        // TODO: We cannot select all contents via. the parent type, see: https://github.com/jackalope/jackalope-doctrine-dbal/issues/217
        $query = $this->documentManager->createQuery(
            'SELECT * FROM [nt:unstructured] AS a WHERE ' . $condition
        );

        $batchSize = 50;
        $offset = $this->resumeManager->getCheckpoint(self::CHECKPOINT_NAME, 0);
        $batchLimit = 4;
        $batchCount = 0;

        // index in batches: indexing in batches means:
        //
        //   1. Jackalope does not need to hydrate all of the PHPCR nodes from the query.
        //   2. We can clear the document manager registry periodically.
        do {
            $this->resumeManager->setCheckpoint(self::CHECKPOINT_NAME, $offset);
            $query->setMaxResults($batchSize);
            $query->setFirstResult($offset);
            $documents = $query->execute();

            if ($documents->count() === 0) {
                break;
            }

            $output->write(PHP_EOL);
            $output->writeln(' <info>offset</info> ' . $offset);
            $result = $this->indexBatch($output, $filter, $documents);
            $output->write(PHP_EOL);

            // this reduces overall memory usage by 1% in one experiment, so is not essential
            $this->documentManager->clear();
            $offset += $batchSize;
            $batchCount++;

            if (null !== $batchLimit && $batchCount > $batchLimit) {
                break;
            }
        } while($result !== false);

        $output->write(PHP_EOL);
        $this->resumeManager->removeCheckpoint(self::CHECKPOINT_NAME);
    }

    private function indexBatch($output, $filter, $documents)
    {
        $progress = new ProgressHelper();
        $progress->start($output, count($documents));

        $document = $documents->current();

        do {
            $error = null;
            if ($document instanceof SecurityBehavior && !empty($document->getPermissions())) {
                $progress->advance();
                continue;
            }

            try {
                $locales = $this->inspector->getLocales($document);

                foreach ($locales as $locale) {
                    try {
                        $this->documentManager->find($document->getUuid(), $locale);
                        $documentClass = get_class($document);

                        if ($filter && !preg_match('{' . $filter . '}', $documentClass)) {
                            continue;
                        }

                        $this->searchManager->index($document, $locale);
                    } catch (\Exception $e) {
                        $error = $e;
                    }
                }
            } catch (\Exception $e) {
                $error = '<error>Error indexing or de-indexing page</error>';
            }

            $progress->advance();

            $output->write(' Mem: ' . number_format(memory_get_usage()) . 'b');

            if ($document instanceof TitleBehavior) {
                $output->write(' Title: ' . $document->getTitle(). "\x1B[0J");
            } else {
                $output->write(' OID: ' . spl_object_hash($document->getTitle()). "\x1B[0J");
            }

            try {
                $documents->next();
                if ($documents->valid()) {
                    $document = $documents->current();
                } else {
                    $document = false;
                }
            } catch (\Exception $e) {
                $this->logError($output, sprintf(
                    'Error when hydrating document: %s',
                    $e->getMessage()
                ));
            }
        } while ($document);

        return true;
    }

    private function logError(OutputInterface $output, $message)
    {
        $output->write(PHP_EOL);
        $output->write(PHP_EOL);
        $output->writeln(sprintf(
            ' <error>%s</error>', $message
        ));
        $output->write(PHP_EOL);
    }
}
