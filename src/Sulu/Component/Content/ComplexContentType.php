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

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * base class of complex content types.
 */
abstract class ComplexContentType implements ContentTypeInterface
{
    public function getDefaultParams(?PropertyInterface $property = null)
    {
        return [];
    }

    public function __get($property)
    {
        if (\method_exists($this, 'get' . \ucfirst($property))) {
            return $this->{'get' . \ucfirst($property)}();
        } else {
            return;
        }
    }

    /**
     * @return bool
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

    public function getDefaultValue()
    {
        return null;
    }

    public function getViewData(PropertyInterface $property)
    {
        return [];
    }

    public function getContentData(PropertyInterface $property)
    {
        return $property->getValue();
    }
}
