<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\ContentBundle\Content\InternalLinksContainer;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Util\ArrayableInterface;

/**
 * content type for internal links selection.
 */
class InternalLinks extends ComplexContentType implements ContentTypeExportInterface
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

    /**
     * @var bool
     */
    private $showDrafts;

    public function __construct(
        ContentQueryExecutorInterface $contentQueryExecutor,
        ContentQueryBuilderInterface $contentQueryBuilder,
        LoggerInterface $logger,
        $template,
        $showDrafts
    ) {
        $this->contentQueryExecutor = $contentQueryExecutor;
        $this->contentQueryBuilder = $contentQueryBuilder;
        $this->logger = $logger;
        $this->template = $template;
        $this->showDrafts = $showDrafts;
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
        $data = [];
        if ($node->hasProperty($property->getName())) {
            $data = $node->getProperty($property->getName())->getString();
        }

        $refs = isset($data) ? $data : [];
        $property->setValue($refs);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedUuids(PropertyInterface $property)
    {
        $data = $property->getValue();

        return isset($data) ? $data : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        return ['properties' => new PropertyParameter('properties', [], 'collection')];
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
            $ids = [];
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
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $data = $property->getValue();
        $container = new InternalLinksContainer(
            isset($data) ? $data : [],
            $this->contentQueryExecutor,
            $this->contentQueryBuilder,
            array_merge($this->getDefaultParams(), $property->getParams()),
            $this->logger,
            $property->getStructure()->getWebspaceKey(),
            $property->getStructure()->getLanguageCode(),
            $this->showDrafts
        );

        return $container->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function exportData($propertyValue)
    {
        if (!is_array($propertyValue) || empty($propertyValue)) {
            return '';
        }

        return json_encode($propertyValue);
    }

    /**
     * {@inheritdoc}
     */
    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $property->setValue(json_decode($value));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }
}
