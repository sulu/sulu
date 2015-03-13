<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\EventListener;

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
    private $structureIndexName;

    public function __construct(
        SessionManagerInterface $sessionManager,
        ContentMapperInterface $contentMapper,
        SearchManagerInterface $searchManager,
        WebspaceManagerInterface $webspaceManager,
        StructureManagerInterface $structureManager,
        SuluNodeHelper $nodeHelper,
        $structureIndexName
    )
    {
        $this->sessionManager = $sessionManager;
        $this->contentMapper = $contentMapper;
        $this->searchManager = $searchManager;
        $this->webspaceManager = $webspaceManager;
        $this->structureManager = $structureManager;
        $this->nodeHelper = $nodeHelper;
        $this->structureIndexName = $structureIndexName;
    }

    /**
     * Prefix url of document with current resourcelocator prefix
     * @param HitEvent $event
     */
    public function onIndexRebuild(IndexRebuildEvent $event)
    {
        $output = $event->getOutput();
        $purge = $event->getPurge();
        $filter = $event->getFilter();
        $session = $this->sessionManager->getSession();

        $sql2 = 'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:snippet"';
        $queryManager = $session->getWorkspace()->getQueryManager();
        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
        $res = $query->execute();

        $count = array();
        $purged = false;

        /** @var Row $row */
        foreach ($res->getRows() as $row) {
            $node = $row->getNode('a');

            $locales = $this->nodeHelper->getLanguagesForNode($node);

            foreach ($locales as $locale) {
                $structure = $this->contentMapper->loadByNode($node, $locale);
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

                if ($purge && false === $purged) {
                    $output->writeln('<comment>Purging index</comment>: ' . $this->structureIndexName);
                    $this->searchManager->purge($this->structureIndexName);
                    $purged = true;
                }

                try {
                    if ($structure->getNodeState() === Structure::STATE_PUBLISHED) {
                        $this->searchManager->index($structure, $locale);
                        $count[$structureClass]['indexed']++;
                    } else {
                        $this->searchManager->deindex($structure, $locale);
                        $count[$structureClass]['deindexed']++;
                    }
                } catch (\Exception $e) {
                    throw $e;
                    $output->writeln(
                        '  [!] <error>Error indexing or de-indexing page (path: ' . $node->getPath() .
                        ', locale: ' . $locale . '): ' . $exc->getMessage() . '</error>'
                    );
                }
            }
        }

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
}
