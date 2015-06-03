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
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Console\Helper\ProgressHelper;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;

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
     * @var DocumentManagerInterface
     */
    private $documentManager;

    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentInspector $inspector,
        SearchManagerInterface $searchManager,
        array $mapping = array()
    ) {
        $this->searchManager = $searchManager;
        $this->mapping = $mapping;
        $this->documentManager = $documentManager;
        $this->inspector = $inspector;
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

        $output->writeln('<info>Rebuilding content index</info>');

        // TODO: We cannot select all contents via. the parent type, see: https://github.com/jackalope/jackalope-doctrine-dbal/issues/217
        $query = $this->documentManager->createQuery(
            'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "sulu:page" or [jcr:mixinTypes] = "sulu:snippet"'
        );

        $count = array();

        if ($purge) {
            $this->purgeContentIndexes($output);
        }

        $documents = $query->execute();
        $progress = new ProgressHelper();
        $progress->start($output, count($documents));

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

    private function purgeContentIndexes($output)
    {
        foreach ($this->mapping as $structureMapping) {
            $structureIndexName = $structureMapping['index'];
            $output->writeln('<comment>Purging index</comment>: ' . $structureIndexName);
            $this->searchManager->purge($structureIndexName);
        }
    }
}
