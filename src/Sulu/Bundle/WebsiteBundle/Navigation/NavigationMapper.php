<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Component\Stopwatch\Stopwatch;

class NavigationMapper implements NavigationMapperInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var ContentQueryExecutorInterface
     */
    private $contentQueryExecutor;

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

    /**
     * @var array
     */
    private $permissions;

    /**
     * @var array
     */
    private $enabledTwigAttributes = [];

    public function __construct(
        ContentMapperInterface $contentMapper,
        ContentQueryExecutorInterface $contentQueryExecutor,
        ContentQueryBuilderInterface $queryBuilder,
        SessionManagerInterface $sessionManager,
        ?Stopwatch $stopwatch = null,
        $permissions = null,
        array $enabledTwigAttributes = [
            'path' => true,
        ]
    ) {
        $this->contentMapper = $contentMapper;
        $this->contentQueryExecutor = $contentQueryExecutor;
        $this->queryBuilder = $queryBuilder;
        $this->sessionManager = $sessionManager;
        $this->stopwatch = $stopwatch;
        $this->permissions = $permissions;
        $this->enabledTwigAttributes = $enabledTwigAttributes;

        if ($enabledTwigAttributes['path'] ?? true) {
            @trigger_deprecation('sulu/sulu', '2.3', 'Enabling the "path" parameter is deprecated.');
        }
    }

    public function getNavigation(
        $parent,
        $webspaceKey,
        $locale,
        $depth = 1,
        $flat = false,
        $context = null,
        $loadExcerpt = false,
        $segmentKey = null
    ) {
        if ($this->stopwatch) {
            $this->stopwatch->start('NavigationMapper::getNavigation');
        }
        $rootDepth = \substr_count($this->sessionManager->getContentNode($webspaceKey)->getPath(), '/');
        $parent = $this->sessionManager->getSession()->getNodeByIdentifier($parent)->getPath();
        $depth = $depth + \substr_count($parent, '/') - $rootDepth;

        $this->queryBuilder->init(
            [
                'context' => $context,
                'parent' => $parent,
                'excerpt' => $loadExcerpt,
                'segmentKey' => $segmentKey,
            ]
        );
        $result = $this->contentQueryExecutor->execute(
            $webspaceKey,
            [$locale],
            $this->queryBuilder,
            $flat,
            $depth,
            null,
            null,
            false,
            $this->permissions[PermissionTypes::VIEW] ?? null
        );

        $result = $this->normalizeResult($result);

        if ($this->stopwatch) {
            $this->stopwatch->stop('NavigationMapper::getNavigation');
        }

        return $result;
    }

    public function getRootNavigation(
        $webspaceKey,
        $locale,
        $depth = 1,
        $flat = false,
        $context = null,
        $loadExcerpt = false,
        $segmentKey = null
    ) {
        if ($this->stopwatch) {
            $this->stopwatch->start('NavigationMapper::getRootNavigation.query');
        }

        $this->queryBuilder->init(['context' => $context, 'excerpt' => $loadExcerpt, 'segmentKey' => $segmentKey]);
        $result = $this->contentQueryExecutor->execute(
            $webspaceKey,
            [$locale],
            $this->queryBuilder,
            $flat,
            $depth,
            null,
            null,
            false,
            $this->permissions[PermissionTypes::VIEW] ?? null
        );

        $result = $this->normalizeResult($result);

        if ($this->stopwatch) {
            $this->stopwatch->stop('NavigationMapper::getRootNavigation.query');
        }

        return $result;
    }

    public function getBreadcrumb($uuid, $webspace, $language)
    {
        $breadcrumbItems = $this->contentMapper->loadBreadcrumb($uuid, $language, $webspace);

        $result = [];
        foreach ($breadcrumbItems as $item) {
            $result[] = $this->contentMapper->load($item->getUuid(), $webspace, $language);
        }
        $result[] = $this->contentMapper->load($uuid, $webspace, $language);

        return $this->generateNavigation($result, $webspace, $language, false, null, true, false);
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
        $breakOnNotInNavigation = false,
        $recursive = true
    ) {
        $result = [];

        /** @var StructureInterface $content */
        foreach ($contents as $content) {
            if ($this->inNavigation($content, $context)) {
                $url = $content->getResourceLocator();
                $title = $content->getTitle();
                $children = $recursive ? $this->generateChildNavigation($content, $webspace, $language, $flat, $context) : [];

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
                    $result = \array_merge($result, $children);
                }
            } elseif (true === $flat) {
                $children = $recursive ? $this->generateChildNavigation($content, $webspace, $language, $flat, $context) : [];
                $result = \array_merge($result, $children);
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
        $children = [];
        if (\is_array($content->getChildren()) && \count($content->getChildren()) > 0) {
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
     * @param string|null $context
     *
     * @return bool
     */
    public function inNavigation(StructureInterface $content, $context = null)
    {
        $contexts = $content->getNavContexts();

        if (Structure::STATE_PUBLISHED !== $content->getNodeState()) {
            // if node state is not published do not show page
            return false;
        }

        if (\is_array($contexts) && (null === $context || \in_array($context, $contexts))) {
            // all contexts or content has context
            return true;
        }

        // do not show
        return false;
    }

    private function normalizeResult(array $result)
    {
        foreach ($result as $key => $item) {
            if (isset($item['children'])) {
                $item['children'] = $this->normalizeResult($item['children']);
            } else {
                $item['children'] = [];
            }

            if (!($this->enabledTwigAttributes['path'] ?? true)) {
                unset($item['path']);
            }

            $result[$key] = $item;
        }

        return $result;
    }
}
