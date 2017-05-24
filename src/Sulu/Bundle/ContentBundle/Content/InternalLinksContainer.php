<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content;

use JMS\Serializer\Annotation\Exclude;
use Psr\Log\LoggerInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Util\ArrayableInterface;

/**
 * Container for InternalLinks, holds the config for a internal links, and lazy loads the structures.
 */
class InternalLinksContainer implements ArrayableInterface
{
    /**
     * The content mapper, which is needed for lazy loading.
     *
     * @Exclude
     *
     * @var ContentQueryExecutorInterface
     */
    private $contentQueryExecutor;

    /**
     * The content mapper, which is needed for lazy loading.
     *
     * @Exclude
     *
     * @var ContentQueryBuilderInterface
     */
    private $contentQueryBuilder;

    /**
     * The params to load.
     *
     * @Exclude
     *
     * @var array
     */
    private $params;

    /**
     * @Exclude
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The key of the webspace.
     *
     * @Exclude
     *
     * @var string
     */
    private $webspaceKey;

    /**
     * The code of the language.
     *
     * @Exclude
     *
     * @var string
     */
    private $languageCode;

    /**
     * @var string[]
     */
    private $ids;

    /**
     * @Exclude
     *
     * @var StructureInterface[]
     */
    private $data;

    /**
     * @var bool
     */
    private $showDrafts;

    public function __construct(
        $ids,
        ContentQueryExecutorInterface $contentQueryExecutor,
        ContentQueryBuilderInterface $contentQueryBuilder,
        $params,
        LoggerInterface $logger,
        $webspaceKey,
        $languageCode,
        $showDrafts
    ) {
        $this->ids = $ids;
        $this->contentQueryExecutor = $contentQueryExecutor;
        $this->contentQueryBuilder = $contentQueryBuilder;
        $this->logger = $logger;
        $this->webspaceKey = $webspaceKey;
        $this->languageCode = $languageCode;
        $this->params = $params;
        $this->showDrafts = $showDrafts;
    }

    /**
     * Lazy loads the data based on the filter criteria from the config.
     *
     * @return StructureInterface[]
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->data = $this->loadData();
        }

        return $this->data;
    }

    /**
     * lazy load data.
     */
    private function loadData()
    {
        $result = [];
        if ($this->ids !== null && count($this->ids) > 0) {
            $this->contentQueryBuilder->init(
                [
                    'ids' => $this->ids,
                    'properties' => (isset($this->params['properties']) ? $this->params['properties']->getValue() : []),
                    'published' => !$this->showDrafts,
                ]
            );
            $pages = $this->contentQueryExecutor->execute(
                $this->webspaceKey,
                [$this->languageCode],
                $this->contentQueryBuilder
            );

            // init vars
            $map = [];

            // map pages
            foreach ($pages as $page) {
                $map[$page['uuid']] = $page;
            }

            foreach ($this->ids as $id) {
                if (isset($map[$id])) {
                    $result[] = $map[$id];
                }
            }
        }

        return $result;
    }

    /**
     * magic getter.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'data':
                return $this->getData();
        }

        return;
    }

    /**
     * magic isset.
     */
    public function __isset($name)
    {
        return $name == 'data';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        return ['ids' => $this->ids];
    }
}
