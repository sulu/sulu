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
 * base class of complex content types.
 */
abstract class ComplexContentType implements ContentTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefaultParams()
    {
        return array();
    }

    /**
     * {@inheritdoc}
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
    public function hasValue(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        return $node->hasProperty($property->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return;
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
