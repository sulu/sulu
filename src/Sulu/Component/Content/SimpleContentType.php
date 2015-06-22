<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use PHPCR\NodeInterface;

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
        // if exist remove property of node
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * returns type of ContentType
     * PRE_SAVE or POST_SAVE.
     *
     * @return int
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
     * returns default parameters.
     *
     * @return array
     */
    public function getDefaultParams()
    {
        return array();
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
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        return $property->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function getReferencedUuids(PropertyInterface $property)
    {
        return array();
    }
}
