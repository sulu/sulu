<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types\SmartContent;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Content\SmartContentContainer;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\PropertyParameter;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Util\ArrayableInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * ContentType for TextEditor.
 */
class SmartContent extends ComplexContentType
{
    /**
     * @var ContentQueryExecutorInterface
     */
    private $contentQuery;

    /**
     * @var ContentQueryBuilderInterface
     */
    private $contentQueryBuilder;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var string
     */
    private $template;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(
        ContentQueryExecutorInterface $contentQuery,
        ContentQueryBuilderInterface $contentQueryBuilder,
        TagManagerInterface $tagManager,
        RequestStack $requestStack,
        $template,
        Stopwatch $stopwatch = null
    ) {
        $this->contentQuery = $contentQuery;
        $this->contentQueryBuilder = $contentQueryBuilder;
        $this->tagManager = $tagManager;
        $this->template = $template;
        $this->requestStack = $requestStack;
        $this->stopwatch = $stopwatch;
    }

    /**
     * returns a template to render a form.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * returns type of ContentType
     * PRE_SAVE or POST_SAVE.
     *
     * @return int
     */
    public function getType()
    {
        return self::PRE_SAVE;
    }

    /**
     * @param $data
     * @param PropertyInterface $property
     */
    protected function setData(
        $data,
        PropertyInterface $property
    ) {
        $property->setValue($data);
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $data = $node->getPropertyValueWithDefault($property->getName(), '{}');
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!empty($data['tags'])) {
            $data['tags'] = $this->tagManager->resolveTagIds($data['tags']);
        }

        $this->setData($data, $property, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview($data, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        if ($data instanceof ArrayableInterface) {
            $data = $data->toArray();
        }

        $this->setData($data, $property, $webspaceKey, $languageCode, $segmentKey, true);
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();
        if ($value instanceof ArrayableInterface) {
            $value = $value->toArray();
        }

        // if whole smart-content container is pushed
        if (isset($value['config'])) {
            $value = $value['config'];
        }

        if (!empty($value['tags'])) {
            $value['tags'] = $this->tagManager->resolveTagNames($value['tags']);
        }

        $node->setProperty($property->getName(), json_encode($value));
    }

    /**
     * remove property from given node.
     *
     * @param NodeInterface $node
     * @param PropertyInterface $property
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultParams()
    {
        $params = parent::getDefaultParams();
        $params['page_parameter'] = new PropertyParameter('page_parameter', 'p');
        $params['properties'] = new PropertyParameter('properties', array(), 'collection');
        $params['present_as'] = new PropertyParameter('present_as', array(), 'collection');

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        $this->getContentData($property);
        $config = $property->getValue();

        $config = array_merge(
            array(
                'dataSource' => null,
                'includeSubFolders' => null,
                'category' => null,
                'tags' => array(),
                'sortBy' => null,
                'sortMethod' => null,
                'presentAs' => null,
                'limitResult' => null,
                'page' => null,
                'hasNextPage' => null,
            ),
            $config
        );

        return $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $params = array_merge(
            $this->getDefaultParams(),
            $property->getParams()
        );

        $data = $property->getValue();

        $container = new SmartContentContainer(
            $this->contentQuery,
            $this->contentQueryBuilder,
            $this->tagManager,
            $params,
            $property->getStructure()->getWebspaceKey(),
            $property->getStructure()->getLanguageCode(),
            // TODO segmentkey
            null,
            $this->stopwatch
        );
        $container->setConfig($data === null || !is_array($data) ? array() : $data);
        $pages = $this->loadData($container, $property, $params);

        $data['page'] = $container->getPage();
        $data['hasNextPage'] = $container->getHasNextPage();
        $property->setValue($data);

        return $pages;
    }

    /**
     * {@inheritDoc}
     */
    public function getReferencedUuids(PropertyInterface $property)
    {
        $content = $this->getContentData($property);
        $uuids = array();

        foreach ($content as $page) {
            $uuids[] = $page['uuid'];
        }

        return $uuids;
    }

    /**
     * load data from container.
     *
     * @param SmartContentContainer $container
     * @param PropertyInterface $property
     * @param PropertyParameter[] $params
     *
     * @return array|\Sulu\Component\Content\StructureInterface[]
     */
    private function loadData(SmartContentContainer $container, PropertyInterface $property, $params)
    {
        if (isset($params['max_per_page'])) {
            // determine current page
            $container->setPage($this->getCurrentPage($params['page_parameter']->getValue()));

            $contentData = $this->getPagedContentData(
                $container,
                $container->getPage(),
                intval($params['max_per_page']->getValue()),
                $property->getStructure()->getUuid()
            );
        } else {
            // set default values
            $container->setPage(1);
            $container->setHasNextPage(false);

            $contentData = $this->getNotPagedContentData(
                $container,
                $property->getStructure()->getUuid()
            );
        }

        return $contentData;
    }

    /**
     * determine current page from current request.
     *
     * @param string $pageParameter
     *
     * @return int
     */
    private function getCurrentPage($pageParameter)
    {
        if ($this->requestStack->getCurrentRequest() !== null) {
            $page = $this->requestStack->getCurrentRequest()->get($pageParameter, 1);
        } else {
            $page = 1;
        }

        return intval($page);
    }

    /**
     * Returns paged content.
     */
    private function getPagedContentData(SmartContentContainer $container, $page, $pageSize, $excludeUuid)
    {
        $config = $container->getConfig();
        $limitResult = isset($config['limitResult']) && !empty($config['limitResult']) ? intval($config['limitResult']) : null;

        $limit = intval($pageSize);
        $offset = ($page - 1) * $limit;

        $position = $limit * $page;
        if ($limitResult !== null && $position >= $limitResult) {
            $limit = $limitResult - $offset;
            $loadLimit = $limit;
        } else {
            $loadLimit = $limit + 1;
        }

        if ($limit < 0) {
            $container->setHasNextPage(false);

            return array();
        }

        $data = $container->getData(array($excludeUuid), $loadLimit, $offset);

        if (sizeof($data) > $limit) {
            $container->setHasNextPage(true);
            $data = array_splice($data, 0, $limit);
        } else {
            $container->setHasNextPage(false);
        }

        return $data;
    }

    /**
     * Returns not paged content.
     *
     * @param SmartContentContainer $container
     * @param string $excludeUuid
     *
     * @return \Sulu\Component\Content\StructureInterface[]
     */
    private function getNotPagedContentData(SmartContentContainer $container, $excludeUuid)
    {
        return $container->getData(array($excludeUuid));
    }
}
