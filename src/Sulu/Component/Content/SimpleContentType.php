<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * Simple implementation of ContentTypes.
 */
abstract class SimpleContentType implements ContentTypeInterface
{
    /**
     * name of content type.
     *
     * @var string
     */
    private $name;

    /**
     * default value if node does not have the property.
     *
     * @var mixed
     */
    private $defaultValue;

    public function __construct($name, $defaultValue = null)
    {
        $this->name = $name;
        $this->defaultValue = $defaultValue;
    }

    /**
     * Returns the name of the content type.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = $this->defaultValue;
        if ($node->hasProperty($property->getName())) {
            $value = $node->getPropertyValue($property->getName());
        }

        $property->setValue($value);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview($data, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $property->setValue($data);
    }

    /**
     * {@inheritdoc}
     */
    public function hasValue(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        return $node->hasProperty($property->getName());
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
        if ($value != null) {
            $node->setProperty($property->getName(), $value);
        } else {
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        // if exist remove property of node
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return ContentTypeInterface::PRE_SAVE;
    }

    /**
     * magic getter for twig templates.
     *
     * @param $property string name of property
     */
    public function __get($property)
    {
        if (method_exists($this, 'get' . ucfirst($property))) {
            return $this->{'get' . ucfirst($property)}();
        } else {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        return $property->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedUuids(PropertyInterface $property)
    {
        return [];
    }
}
