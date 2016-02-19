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
use Massive\Bundle\SearchBundle\Search\Reindex\ResumeManager;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Metadata\BaseMetadataFactory;
use Symfony\Component\Console\Helper\ProgressBar;
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
     * @var ResumeManager
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


        $batchSize = 50;
        $offset = $this->resumeManager->getCheckpoint(self::CHECKPOINT_NAME, 0);

        // TODO: make this an option.
        $batchLimit = null;
        $batchCount = 0;

        // index in batches: indexing in batches means:
        //
        //   1. Jackalope does not need to hydrate all of the PHPCR nodes from the query.
        //   2. We can clear the document manager registry periodically.
        //
        // neither of the above points drastically reduce memory usage.
        do {
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
            ++$batchCount;

            if (null !== $batchLimit && $batchCount > $batchLimit) {
                break;
            }
            $this->resumeManager->setCheckpoint(self::CHECKPOINT_NAME, $offset);
        } while ($result !== false);

        $output->write(PHP_EOL);
        $this->resumeManager->removeCheckpoint(self::CHECKPOINT_NAME);
    }

    private function indexBatch($output, $filter, $documents)
    {
        $progress = new ProgressBar($output);
        $progress->start(count($documents));

        while ($documents->valid()) {
            try {
                $document = $documents->current();
            } catch (\Exception $e) {
                $this->logError($output, sprintf(
                   'Error when hydrating document'
               ), $e);
                $documents->next();
                $progress->advance();
                continue;
            }

            if ($document instanceof SecurityBehavior && !empty($document->getPermissions())) {
                $documents->next();
                $progress->advance();
                continue;
            }

            try {
                $locales = $this->inspector->getLocales($document);
            } catch (\Exception $e) {
                $this->logError($output, 'Error indexing page', $e);
                $documents->next();
                $progress->advance();
                continue;
            }

            foreach ($locales as $locale) {
                try {
                    $documentClass = get_class($document);

                    if ($filter && !preg_match('{' . $filter . '}', $documentClass)) {
                        continue;
                    }

                    $this->documentManager->find($document->getUuid(), $locale);
                    $this->searchManager->index($document, $locale);
                } catch (\Exception $e) {
                    $this->logError($output, sprintf(
                        'Error indexing locale "%s"',
                        $locale
                    ), $e);
                }
            }

            $progress->advance();

            $output->write(' Mem: ' . number_format(memory_get_usage()) . 'b');

            if ($document instanceof TitleBehavior) {
                $output->write(' Title: ' . $document->getTitle() . "\x1B[0J");
            } else {
                $output->write(' OID: ' . spl_object_hash($document) . "\x1B[0J");
            }

            $documents->next();
        };

        return true;
    }

    private function logError(OutputInterface $output, $message, \Exception $exception)
    {
        $output->write(PHP_EOL);
        $output->write(PHP_EOL);
        $output->writeln(sprintf(
            ' <error>%s</error> %s', $message, $exception->getMessage()
        ));
        $output->write(PHP_EOL);
    }
}
