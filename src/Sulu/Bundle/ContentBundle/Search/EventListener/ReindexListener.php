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
     * @var BaseMetadataFactory
     */
    private $baseMetadataFactory;

    public function __construct(
        DocumentManager $documentManager,
        DocumentInspector $inspector,
        SearchManagerInterface $searchManager,
        BaseMetadataFactory $baseMetadataFactory,
        array $mapping = []
    ) {
        $this->searchManager = $searchManager;
        $this->mapping = $mapping;
        $this->documentManager = $documentManager;
        $this->inspector = $inspector;
        $this->baseMetadataFactory = $baseMetadataFactory;
    }

    /**
     * Prefix url of document with current resourcelocator prefix.
     *
     * @param IndexRebuildEvent $event
     */
    public function onIndexRebuild(IndexRebuildEvent $event)
    {
        $output = $event->getOutput();
        $filter = $event->getFilter();

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

        $count = [];

        $documents = $query->execute();
        $progress = new ProgressHelper();
        $progress->start($output, count($documents));

        foreach ($documents as $document) {
            if ($document instanceof SecurityBehavior && !empty($document->getPermissions())) {
                $progress->advance();
                continue;
            }

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
                    ++$count[$documentClass];
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

        $output->writeln('');

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
}
