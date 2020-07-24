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
use Sulu\Component\Content\SimpleContentType;

class SegmentSelect extends SimpleContentType
{
    public function __construct()
    {
        parent::__construct('SegmentSelect', '{}');
    }

    public function write(NodeInterface $node, PropertyInterface $property, $userId, $webspaceKey, $languageCode, $segmentKey)
    {
        parent::write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);

        // write a separate property for per webspace to make segment queryable in the smart content data provider
        foreach ($property->getValue() as $webspaceKeyValue => $segmentKeyValue) {
            $webspaceSegmentPropertyName = $property->getName() . '-' . $webspaceKeyValue;
            $node->setProperty($webspaceSegmentPropertyName, $this->removeIllegalCharacters($segmentKeyValue));
        }
    }

    protected function encodeValue($value)
    {
        return \json_encode($value);
    }

    protected function decodeValue($value)
    {
        if (!\is_string($value)) {
            $value = $this->defaultValue;
        }

        return \json_decode($value, true);
    }

    public function exportData($propertyValue)
    {
        return $this->encodeValue($propertyValue);
    }

    public function importData(NodeInterface $node, PropertyInterface $property, $value, $userId, $webspaceKey, $languageCode, $segmentKey = null)
    {
        parent::importData(
            $node,
            $property,
            $this->decodeValue($value),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
    }
}
