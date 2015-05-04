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

use Massive\Bundle\SearchBundle\Search\Event\HitEvent;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Massive\Bundle\SearchBundle\Search\Event\IndexRebuildEvent;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Structure;
use Symfony\Component\Console\Helper\ProgressHelper;

/**
 * Listen to for new hits. If document instance of structure
 * prefix the current resource locator prefix to the URL
 */
class ReindexListener
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var string
     */
    private $mapping;

    public function __construct(
        SessionManagerInterface $sessionManager,
        ContentMapperInterface $contentMapper,
        SearchManagerInterface $searchManager,
        WebspaceManagerInterface $webspaceManager,
        StructureManagerInterface $structureManager,
        SuluNodeHelper $nodeHelper,
        array $mapping = array()
    ) {
        $this->sessionManager = $sessionManager;
        $this->contentMapper = $contentMapper;
        $this->searchManager = $searchManager;
        $this->webspaceManager = $webspaceManager;
        $this->structureManager = $structureManager;
        $this->nodeHelper = $nodeHelper;
        $this->mapping = $mapping;
    }

    /**
     * Prefix url of document with current resourcelocator prefix
     * @param IndexRebuildEvent $event
     */
    public function onIndexRebuild(IndexRebuildEvent $event)
    {
        $output = $event->getOutput();
        $purge = $event->getPurge();
        $filter = $event->getFilter();
        $session = $this->sessionManager->getSession();

        $output->writeln('<info>Rebuilding content index</info>');

        // TODO: We cannot select all contents via. the parent type, see: https://github.com/jackalope/jackalope-doctrine-dbal/issues/217
        $sql2 = 'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "sulu:page" or [jcr:mixinTypes] = "sulu:snippet"';
        $queryManager = $session->getWorkspace()->getQueryManager();
        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
        $result = $query->execute();

        $count = array();

        if ($purge) {
            $this->purgeContentIndexes($output);
        }

        $rows = $res->getRows();

        $progress = new ProgressHelper();
        $progress->start($output, count($rows));

        /** @var Row $row */
        foreach ($rows as $row) {
            $node = $row->getNode('a');

            $locales = $this->nodeHelper->getLanguagesForNode($node);

            foreach ($locales as $locale) {
                try {
                    $structure = $this->contentMapper->loadByNode($node, $locale, null, false, true, false);
                    $structureClass = get_class($structure);

                    if (!isset($count[$structureClass])) {
                        $count[$structureClass] = array(
                            'indexed' => 0,
                            'deindexed' => 0,
                        );
                    }

                    if ($filter && !preg_match('{' . $filter . '}', get_class($structure))) {
                        continue;
                    }

                    if ($structure->getNodeState() === Structure::STATE_PUBLISHED) {
                        $this->searchManager->index($structure, $locale);
                        $count[$structureClass]['indexed']++;
                    } else {
                        $this->searchManager->deindex($structure, $locale);
                        $count[$structureClass]['deindexed']++;
                    }
                } catch (\Exception $e) {
                    $output->writeln(
                        '  [!] <error>Error indexing or de-indexing page (path: ' . $node->getPath() .
                        ', locale: ' . $locale . '): ' . $e->getMessage() . '</error>'
                    );
                }
            }

            $progress->advance();
        }

        $output->writeln('');

        foreach ($count as $className => $stats) {
            if ($stats['indexed'] == 0 && $stats['deindexed'] == 0) {
                continue;
            }

            $output->writeln(sprintf(
                '<comment>Content</comment>: %s <info>%s</info> indexed, <info>%s</info> deindexed',
                $className,
                $stats['indexed'],
                $stats['deindexed']
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
