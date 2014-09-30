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
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Sulu\Component\Util\ArrayableInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * ContentType for TextEditor
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

    function __construct(
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
     * returns a template to render a form
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * returns type of ContentType
     * PRE_SAVE or POST_SAVE
     * @return int
     */
    public function getType()
    {
        return self::PRE_SAVE;
    }

    /**
     * @param $data
     * @param PropertyInterface $property
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     * @param bool $preview
     */
    protected function setData(
        $data,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey,
        $preview = false
    ) {
        $smartContent = new SmartContentContainer(
            $this->contentQuery,
            $this->contentQueryBuilder,
            $this->tagManager,
            array_merge($this->getDefaultParams(), $property->getParams()),
            $webspaceKey,
            $languageCode,
            $segmentKey,
            $preview,
            $this->stopwatch
        );
        $smartContent->setConfig($data === null || !is_array($data) ? array() : $data);
        $property->setValue($smartContent);
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $data = json_decode($node->getPropertyValueWithDefault($property->getName(), '{}'), true);

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
     * remove property from given node
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
        $params['page_parameter'] = 'p';
        $params['properties'] = array();

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        return $property->getValue()->getConfig();
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

        $data = $property->getValue()->getData();

        // paginate
        if (isset($params['max_per_page'])) {
            $page = $this->requestStack->getCurrentRequest()->get($params['page_parameter'], 1);
            $data = array_slice($data, ($page - 1) * $params['max_per_page'], $params['max_per_page']);
        }

        return $data;
    }
}
