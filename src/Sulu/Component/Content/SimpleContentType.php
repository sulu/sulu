<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use Jackalope\NodeType\NodeProcessor;
use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * Simple implementation of ContentTypes.
 */
abstract class SimpleContentType implements ContentTypeInterface, ContentTypeExportInterface
{
    /**
     * @param string $name
     * @param mixed $defaultValue
     */
    public function __construct(
        private $name,
        protected $defaultValue = null
    ) {
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

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = $this->defaultValue;
        if ($node->hasProperty($property->getName())) {
            $value = $node->getPropertyValue($property->getName());
        }

        $property->setValue($this->decodeValue($value));

        return $value;
    }

    public function hasValue(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        return $node->hasProperty($property->getName());
    }

    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();
        if (null != $value) {
            $node->setProperty($property->getName(), $this->removeIllegalCharacters($this->encodeValue($value)));
        } else {
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }
    }

    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        // if exist remove property of node
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * magic getter for twig templates.
     *
     * @param string $property name of property
     */
    public function __get($property)
    {
        if (\method_exists($this, 'get' . \ucfirst($property))) {
            return $this->{'get' . \ucfirst($property)}();
        } else {
            return;
        }
    }

    public function getDefaultParams(?PropertyInterface $property = null)
    {
        return [];
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function getViewData(PropertyInterface $property)
    {
        return [];
    }

    public function getContentData(PropertyInterface $property)
    {
        return $property->getValue();
    }

    public function exportData($propertyValue)
    {
        if (\is_bool($propertyValue)) {
            if ($propertyValue) {
                return '1';
            }

            return '';
        }

        if (\is_string($propertyValue)) {
            return $propertyValue;
        }

        if (\is_string($this->defaultValue)) {
            return $this->defaultValue;
        }

        if (\is_bool($this->defaultValue)) {
            if ($this->defaultValue) {
                return '1';
            }

            return '';
        }

        if (\is_array($propertyValue)) {
            return \json_encode($propertyValue);
        }

        if (\is_numeric($propertyValue)) {
            return (string) $propertyValue;
        }

        return '';
    }

    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $property->setValue($value);
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * Remove illegal characters from content string, else PHPCR would throw an `PHPCR\ValueFormatException`
     * if an illegal characters is detected.
     *
     * @param string|int $content
     *
     * @return string|int
     */
    protected function removeIllegalCharacters($content)
    {
        if (\is_string($content)) {
            return \preg_replace(NodeProcessor::VALIDATE_STRING, '', $content);
        }

        return $content;
    }

    /**
     * Prepares value for database.
     */
    protected function encodeValue($value)
    {
        return $value;
    }

    /**
     * Decodes value from database.
     */
    protected function decodeValue($value)
    {
        return $value;
    }
}
