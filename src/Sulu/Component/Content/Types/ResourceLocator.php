<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RLPStrategyInterface;

/**
 * Class ResourceLocator
 * @package Sulu\Component\Content\Types
 */
class ResourceLocator extends ComplexContentType implements ResourceLocatorInterface
{
    /**
     * @var RlpStrategyInterface
     */
    private $strategy;

    /**
     * template for form generation
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
        if ($value != null && $value != '') {
            $old = $this->getResourceLocator($node, $webspaceKey, $languageCode, $segmentKey);
            if ($old !== '/') {
                if ($old != null) {
                    $this->getStrategy()->move($old, $value, $webspaceKey, $languageCode, $segmentKey);
                } else {
                    $this->getStrategy()->save($node, $value, $webspaceKey, $languageCode, $segmentKey);
                }
                $node->setProperty($property->getName(), $value);
            }
        } else {
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }
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
        $this->strategy->deleteByPath($property->getValue(), $webspaceKey, $languageCode, $segmentKey);
        $node->remove($property->getName());
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
}
