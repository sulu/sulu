<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Types\Rlp\Strategy\RLPStrategyInterface;

/**
 * Class ResourceLocator.
 */
class ResourceLocator extends ComplexContentType implements ResourceLocatorInterface, ContentTypeExportInterface
{
    /**
     * @var RlpStrategyInterface
     */
    private $strategy;

    /**
     * template for form generation.
     *
     * @var string
     */
    private $template;

    public function __construct(RlpStrategyInterface $strategy, $template)
    {
        $this->strategy = $strategy;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        if ($node->hasProperty($property->getName())) {
            $value = $node->getPropertyValue($property->getName());

            $property->setValue($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasValue(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        try {
            $this->getResourceLocator($node, $webspaceKey, $languageCode, $segmentKey);

            return true;
        } catch (ResourceLocatorNotFoundException $ex) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview($data, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $property->setValue($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceLocator(NodeInterface $node, $webspaceKey, $languageCode, $segmentKey = null)
    {
        try {
            $value = $this->getStrategy()->loadByContent($node, $webspaceKey, $languageCode, $segmentKey);
        } catch (ResourceLocatorNotFoundException $ex) {
            $value = null;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceLocatorByUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        try {
            $value = $this->getStrategy()->loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey);
        } catch (ResourceLocatorNotFoundException $ex) {
            $value = null;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function loadHistoryByUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        return $this->getStrategy()->loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByPath($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $this->getStrategy()->deleteByPath($path, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function restoreByPath($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $this->getStrategy()->restoreByPath($path, $webspaceKey, $languageCode, $segmentKey);
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
        $segmentKey = null
    ) {
        $value = $property->getValue();

        if ($value === null || $value === '') {
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);

            return;
        }

        $this->writeUrl(
            $node,
            $property->getName(),
            $value,
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
    }

    /**
     * @param NodeInterface $node
     * @param $propertyName
     * @param $url
     * @param $userId
     * @param $webspaceKey
     * @param $languageCode
     * @param string $segmentKey
     */
    protected function writeUrl(
        NodeInterface $node,
        $propertyName,
        $url,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $propertyValue = $node->getPropertyValueWithDefault($propertyName, null);
        $treeValue = $this->getResourceLocator($node, $webspaceKey, $languageCode, $segmentKey);
        if ($treeValue === '/') {
            return;
        }

        // only if property value is the same as tree value (is different in move / copy / rename workflow)
        // or the tree value does not exist
        if ($treeValue === null) {
            $this->getStrategy()->save($node, $url, $userId, $webspaceKey, $languageCode, $segmentKey);
        } elseif ($propertyValue === $treeValue && $propertyValue != $url) {
            $this->getStrategy()->move(
                $treeValue,
                $url,
                $node,
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            );
        }

        $node->setProperty($propertyName, $url);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $this->removeUrl(
            $node,
            $property->getName(),
            $property->getValue(),
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
    }

    /**
     * @param NodeInterface $node
     * @param string $propertyName
     * @param string $url
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     */
    protected function removeUrl(
        NodeInterface $node,
        $propertyName,
        $url,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $this->strategy->deleteByPath($url, $webspaceKey, $languageCode, $segmentKey);

        if ($node->hasProperty($propertyName)) {
            $node->getProperty($propertyName)->remove();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadContentNodeUuid($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null)
    {
        return $this->getStrategy()->loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getStrategy()
    {
        // TODO get strategy from ???
        return $this->strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return ContentTypeInterface::POST_SAVE;
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
    public function exportData($propertyValue)
    {
        if (is_string($propertyValue)) {
            return $propertyValue;
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function importData(
        NodeInterface $node,
        $name,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        if ($value === null || $value === '') {
            $this->removeUrl(
                $node,
                $name,
                $value,
                $webspaceKey,
                $languageCode,
                $segmentKey
            );

            return;
        }

        $this->writeUrl(
            $node,
            $name,
            $value,
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
    }

}
