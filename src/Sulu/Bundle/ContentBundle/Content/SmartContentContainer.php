<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content;

use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Util\ArrayableInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Container for SmartContent, holds the config for a smart content, and lazy loads the structures meeting its criteria.
 */
class SmartContentContainer implements ArrayableInterface
{
    /**
     * @var ContentQueryExecutorInterface
     * @Exclude
     */
    private $contentQueryExecutor;

    /**
     * @var ContentQueryBuilderInterface
     * @Exclude
     */
    private $contentQueryBuilder;

    /**
     * @var array
     * @Exclude
     */
    private $params;

    /**
     * Required for resolving the Tags to ids.
     *
     * @var TagManagerInterface
     * @Exclude
     */
    private $tagManager;

    /**
     * The key of the webspace for this smartcontent instance.
     *
     * @var string
     */
    private $webspaceKey;

    /**
     * The code of the language for this smartcontent instance.
     *
     * @var string
     */
    private $languageCode;

    /**
     * Contains all the configuration for the smart content.
     *
     * @var array
     */
    private $config = array();

    /**
     * Stores all the structure meeting the filter criteria in the config.
     * Will be lazy loaded when accessed.
     *
     * @var StructureInterface[]
     */
    private $data = null;

    /**
     * contains current page.
     *
     * @var int
     */
    private $page;

    /**
     * indicates data has next page.
     *
     * @var bool
     */
    private $hasNextPage;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * Constructor.
     */
    public function __construct(
        ContentQueryExecutorInterface $contentQueryExecutor,
        ContentQueryBuilderInterface $contentQueryBuilder,
        TagManagerInterface $tagManager,
        $params,
        $webspaceKey,
        $languageCode,
        $segmentKey,
        Stopwatch $stopwatch = null
    ) {
        $this->contentQueryExecutor = $contentQueryExecutor;
        $this->contentQueryBuilder = $contentQueryBuilder;
        $this->tagManager = $tagManager;
        $this->webspaceKey = $webspaceKey;
        $this->languageCode = $languageCode;
        $this->params = $params;
        $this->stopwatch = $stopwatch;
    }

    /**
     * Sets the config for this container.
     *
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the config for this container.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->config;

        return $config;
    }

    /**
     * Lazy loads the data based on the filter criteria from the config.
     *
     * @param array $excludeUuids
     * @param int $limit
     * @param int $offset
     *
     * @return StructureInterface[]
     */
    public function getData($excludeUuids = array(), $limit = null, $offset = null)
    {
        if ($this->data === null) {
            // resolve tagNames to ids for loading data
            $config = $this->getConfig();
            if (!empty($config['tags'])) {
                $config['tags'] = $this->tagManager->resolveTagNames($config['tags']);
            }

            // determine limit
            if ($limit === null && isset($config['limitResult'])) {
                $limit = $config['limitResult'];
            }

            $this->data = $this->loadData($config, $excludeUuids, $limit, $offset);
        }

        return $this->data;
    }

    /**
     * lazy load data.
     */
    private function loadData($config, $excludeUuids, $limit, $offset)
    {
        if ($this->stopwatch) {
            $this->stopwatch->start('SmartContent:loadData');
        }
        $result = array();
        if (array_key_exists('dataSource', $config) && $config['dataSource'] !== '') {
            $this->contentQueryBuilder->init(
                array('config' => $config, 'properties' => $this->params['properties']->getValue(), 'excluded' => $excludeUuids)
            );
            $result = $this->contentQueryExecutor->execute(
                $this->webspaceKey,
                array($this->languageCode),
                $this->contentQueryBuilder,
                true,
                -1,
                $limit,
                $offset
            );
        }

        if ($this->stopwatch) {
            $this->stopwatch->stop('SmartContent:loadData');
        }

        return $result;
    }

    /**
     * Returns current page.
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set current page.
     *
     * @param int $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * Return indicator of next page.
     *
     * @return bool
     */
    public function getHasNextPage()
    {
        return $this->hasNextPage;
    }

    /**
     * Set next page indicator.
     *
     * @param bool $hasNextPage
     */
    public function setHasNextPage($hasNextPage)
    {
        $this->hasNextPage = $hasNextPage;
    }

    /**
     * magic getter.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'data':
                return $this->getData();
            case 'config':
                return $this->getConfig();
        }

        return;
    }

    /**
     * magic isset.
     */
    public function __isset($name)
    {
        return ($name == 'data' || $name == 'config');
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        return $this->getConfig();
    }
}
