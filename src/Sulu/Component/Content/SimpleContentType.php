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
 * Simple implementation of ContentTypes
 */
abstract class SimpleContentType implements ContentTypeInterface
{
    /**
     * name of content type
     * @var string
     */
    private $name;

    /**
     * default value if node does not have the property
     * @var mixed
     */
    private $defaultValue;

    function __construct($name, $defaultValue = null)
    {
        $this->name = $name;
        $this->defaultValue = $defaultValue;
    }

    /**
     * Returns the name of the content type
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * reads the value for given property out of the database + sets the value of the property
     * @param NodeInterface $node to get data
     * @param PropertyInterface $property to set data
     * @return mixed
     */
    public function get(NodeInterface $node, PropertyInterface $property)
    {
        $value = $this->defaultValue;
        if ($node->hasProperty($property->getName())) {
            $value = $node->getPropertyValue($property->getName());
        }

        $property->setValue($value);

        return $value;
    }

    /**
     * save the value from given property
     * @param NodeInterface $node to set data
     * @param PropertyInterface $property property to get data
     * @return mixed
     */
    public function set(NodeInterface $node, PropertyInterface $property)
    {
        $value = $property->getValue();
        if ($value != null) {
            $node->setProperty($property->getName(), $property->getValue());
        } else {
            $this->remove($node, $property);
        }
    }

    /**
     * remove property from given node
     * @param NodeInterface $node
     * @param PropertyInterface $property
     */
    public function remove(NodeInterface $node, PropertyInterface $property)
    {
        // if exist remove property of node
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * returns type of ContentType
     * PRE_SAVE or POST_SAVE
     * @return int
     */
    public function getType()
    {
        return ContentTypeInterface::PRE_SAVE;
    }

    /**
     * magic getter for twig templates
     * @param $property string name of property
     * @return null
     */
    public function __get($property)
    {
        if (method_exists($this, 'get' . ucfirst($property))) {
            return $this->{'get' . ucfirst($property)}();
        } else {
            return null;
        }
    }
}
