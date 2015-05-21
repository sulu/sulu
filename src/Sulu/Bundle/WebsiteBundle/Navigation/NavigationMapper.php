<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * {@inheritdoc}
 */
class NavigationMapper implements NavigationMapperInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var ContentQueryExecutorInterface
     */
    private $contentQuery;

    /**
     * @var ContentQueryBuilderInterface
     */
    private $queryBuilder;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(
        ContentMapperInterface $contentMapper,
        ContentQueryExecutorInterface $contentQuery,
        ContentQueryBuilderInterface $queryBuilder,
        SessionManagerInterface $sessionManager,
        Stopwatch $stopwatch = null
    ) {
        $this->contentMapper = $contentMapper;
        $this->contentQuery = $contentQuery;
        $this->queryBuilder = $queryBuilder;
        $this->sessionManager = $sessionManager;
        $this->stopwatch = $stopwatch;
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigation(
        $parent,
        $webspaceKey,
        $locale,
        $depth = 1,
        $flat = false,
        $context = null,
        $loadExcerpt = false
    ) {
        if ($this->stopwatch) {
            $this->stopwatch->start('NavigationMapper::getNavigation');
        }
        $rootDepth = substr_count($this->sessionManager->getContentNode($webspaceKey)->getPath(), '/');
        $parent = $this->sessionManager->getSession()->getNodeByIdentifier($parent)->getPath();
        $depth = $depth + substr_count($parent, '/') - $rootDepth;

        $this->queryBuilder->init(
            array(
                'context' => $context,
                'parent' => $parent,
                'excerpt' => $loadExcerpt,
            )
        );
        $result = $this->contentQuery->execute($webspaceKey, array($locale), $this->queryBuilder, $flat, $depth);

        foreach ($result as $item) {
            if (!isset($item['children'])) {
                $item['children'] = array();
            }
        }

        if ($this->stopwatch) {
            $this->stopwatch->stop('NavigationMapper::getNavigation');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootNavigation(
        $webspaceKey,
        $locale,
        $depth = 1,
        $flat = false,
        $context = null,
        $loadExcerpt = false
    ) {
        if ($this->stopwatch) {
            $this->stopwatch->start('NavigationMapper::getRootNavigation.query');
        }

        $this->queryBuilder->init(array('context' => $context, 'excerpt' => $loadExcerpt));
        $result = $this->contentQuery->execute($webspaceKey, array($locale), $this->queryBuilder, $flat, $depth);

        for ($i = 0; $i < sizeof($result); $i++) {
            if (!isset($result[$i]['children'])) {
                $result[$i]['children'] = array();
            }
        }

        if ($this->stopwatch) {
            $this->stopwatch->stop('NavigationMapper::getRootNavigation.query');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getBreadcrumb($uuid, $webspace, $language)
    {
        $breadcrumbItems = $this->contentMapper->loadBreadcrumb($uuid, $language, $webspace);

        $result = array();
        foreach ($breadcrumbItems as $item) {
            if ($item->getDepth() === 0) {
                $result[] = $this->contentMapper->loadStartPage($webspace, $language);
            } else {
                $result[] = $this->contentMapper->load($item->getUuid(), $webspace, $language);
            }
        }
        $result[] = $this->contentMapper->load($uuid, $webspace, $language);

        return $this->generateNavigation($result, $webspace, $language, false, null, true);
    }

    /**
     * generate navigation items for given contents.
     */
    private function generateNavigation(
        $contents,
        $webspace,
        $language,
        $flat = false,
        $context = null,
        $breakOnNotInNavigation = false
    ) {
        $result = array();

        /** @var StructureInterface $content */
        foreach ($contents as $content) {
            if ($this->inNavigation($content, $context)) {
                $url = $content->getResourceLocator();
                $title = $content->getNodeName();
                $children = $this->generateChildNavigation($content, $webspace, $language, $flat, $context);

                if (false === $flat) {
                    $result[] = new NavigationItem(
                        $title,
                        $url,
                        isset($content->getExt()['excerpt']) ? $content->getExt()['excerpt'] : null,
                        $children,
                        $content->getUuid(),
                        $content->getNodeType()
                    );
                } else {
                    $result[] = new NavigationItem(
                        $title,
                        $url,
                        isset($content->getExt()['excerpt']) ? $content->getExt()['excerpt'] : null,
                        null,
                        $content->getUuid(),
                        $content->getNodeType()
                    );
                    $result = array_merge($result, $children);
                }
            } elseif (true === $flat) {
                $children = $this->generateChildNavigation($content, $webspace, $language, $flat, $context);
                $result = array_merge($result, $children);
            } elseif ($breakOnNotInNavigation) {
                break;
            }
        }

        return $result;
    }

    /**
     * generate child navigation of given content.
     */
    private function generateChildNavigation(
        StructureInterface $content,
        $webspace,
        $language,
        $flat = false,
        $context = null
    ) {
        $children = array();
        if (is_array($content->getChildren()) && sizeof($content->getChildren()) > 0) {
            $children = $this->generateNavigation(
                $content->getChildren(),
                $webspace,
                $language,
                $flat,
                $context
            );
        }

        return $children;
    }

    /**
     * checks if content should be displayed.
     *
     * @param StructureInterface $content
     * @param string|null $context
     *
     * @return bool
     */
    public function inNavigation(StructureInterface $content, $context = null)
    {
        $contexts = $content->getNavContexts();

        if ($content->getNodeState() !== Structure::STATE_PUBLISHED) {
            // if node state is not published do not show page
            return false;
        }

        if (is_array($contexts) && ($context === null || in_array($context, $contexts))) {
            // all contexts or content has context
            return true;
        }

        // do not show
        return false;
    }
}
