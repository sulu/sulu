<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;

class SegmentSelect extends ComplexContentType
{
    const SEPARATOR = '-';

    public function write(NodeInterface $node, PropertyInterface $property, $userId, $webspaceKey, $languageCode, $segmentKey)
    {
        // write a separate property for per webspace to make segment queryable in the smart content data provider
        foreach ($property->getValue() as $webspaceKeyValue => $segmentKeyValue) {
            $node->setProperty(
                $this->getWebspaceSegmentPropertyName($property, $webspaceKeyValue),
                $segmentKeyValue
            );
        }
    }

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = [];
        foreach ($this->findProperties($node, $property) as $webspaceProperty) {
            $value[\str_replace($this->getPrefix($property), '', $webspaceProperty->getName())] = $webspaceProperty->getValue();
        }
        $property->setValue($value);
    }

    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        // if exist remove property of node
        foreach ($this->findProperties($node, $property) as $webspaceProperty) {
            $webspaceProperty->remove();
        }
    }

    private function findProperties(NodeInterface $node, PropertyInterface $property)
    {
        return $node->getProperties($this->getPrefix($property) . '*');
    }

    private function getWebspaceSegmentPropertyName(PropertyInterface $property, string $webspaceKeyValue)
    {
        return $this->getPrefix($property) . $webspaceKeyValue;
    }

    private function getPrefix(PropertyInterface $property)
    {
        return $property->getName() . static::SEPARATOR;
    }
}
