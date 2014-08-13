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

use JMS\Serializer\Serializer;
use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Content\InternalLinksContainer;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\PropertyInterface;

/**
 * content type for internal links selection
 * @package Sulu\Bundle\ContentBundle\Content\Types
 */
class InternalLinks extends ComplexContentType
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var string
     */
    private $template;

    /**
     * @var \JMS\Serializer\Serializer
     */
    private $serializer;

    function __construct(ContentMapperInterface $contentMapper, Serializer $serializer, $template)
    {
        $this->contentMapper = $contentMapper;
        $this->serializer = $serializer;
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
        $data = json_decode($node->getPropertyValueWithDefault($property->getName(), '{}'), true);

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
        $this->setData($data, $property, $webspaceKey, $languageCode);
    }

    /**
     * set data to property
     * @param string[] $data ids of images
     * @param PropertyInterface $property
     * @param string $webspaceKey
     * @param string $languageCode
     */
    private function setData($data, PropertyInterface $property, $webspaceKey, $languageCode)
    {
        $container = new InternalLinksContainer(
            isset($data['ids']) ? $data['ids'] : array(),
            $this->contentMapper,
            $this->serializer,
            $webspaceKey,
            $languageCode
        );

        $property->setValue($container);
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

        // if whole container is pushed
        if (isset($value['data'])) {
            unset($value['data']);
        }

        // set value to node
        $node->setProperty($property->getName(), json_encode($value));
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
        $node->getProperty($property->getName())->remove();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }
} 
