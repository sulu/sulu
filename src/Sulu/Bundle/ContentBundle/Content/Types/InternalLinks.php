<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\Util\UUIDHelper;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\ContentBundle\Content\InternalLinksContainer;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Util\ArrayableInterface;

/**
 * content type for internal links selection
 * @package Sulu\Bundle\ContentBundle\Content\Types
 */
class InternalLinks extends ComplexContentType
{
    /**
     * @var ContentQueryExecutorInterface
     */
    private $contentQueryExecutor;
    /**
     * @var ContentQueryBuilderInterface
     */
    private $contentQueryBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $template;

    public function __construct(
        ContentQueryExecutorInterface $contentQueryExecutor,
        ContentQueryBuilderInterface $contentQueryBuilder,
        LoggerInterface $logger,
        $template
    )
    {
        $this->contentQueryExecutor = $contentQueryExecutor;
        $this->contentQueryBuilder = $contentQueryBuilder;
        $this->logger = $logger;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::PRE_SAVE;
    }

    /**
     * {@inheritdoc}
     */
    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $data = $node->getPropertyValueWithDefault($property->getName(), array());

        $this->setData($data, $property, $webspaceKey, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview(
        $data,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($data instanceof ArrayableInterface) {
            $data = $data->toArray();
        }

        $this->setData($data, $property, $webspaceKey, $languageCode);
    }

    /**
     * {@inheritDoc}
     */
    public function getReferencedUuids(PropertyInterface $property)
    {
        $data = $property->getValue();
        $uuids = isset($data) ? $data : array();

        return $uuids;
    }

    /**
     * set data to property
     * @param string[] $data ids of images
     * @param PropertyInterface $property
     */
    private function setData($data, PropertyInterface $property)
    {
        $property->setValue($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams()
    {
        return array('properties' => array());
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

        if (isset($value)) {
            // remove not existing ids
            $session = $node->getSession();
            $selectedNodes = $session->getNodesByIdentifier($value);
            $ids = array();
            foreach ($selectedNodes as $selectedNode) {
                if ($selectedNode->getIdentifier() === $node->getIdentifier()) {
                    throw new \InvalidArgumentException('You are not allowed to link a page to itself!');
                }
                $ids[] = $selectedNode->getIdentifier();
            }
            $value = $ids;
        }

        // set value to node
        $node->setProperty($property->getName(), $value, PropertyType::REFERENCE);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritDoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $data = $property->getValue();
        $container = new InternalLinksContainer(
            isset($data) ? $data : array(),
            $this->contentQueryExecutor,
            $this->contentQueryBuilder,
            array_merge($this->getDefaultParams(), $property->getParams()),
            $this->logger,
            $property->getStructure()->getWebspaceKey(),
            $property->getStructure()->getLanguageCode()
        );

        return $container->getData();
    }
}
