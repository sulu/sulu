<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for Number.
 */
class Number extends SimpleContentType
{
    public function __construct()
    {
        parent::__construct('Number');
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
        if (\is_numeric($value)) {
            $node->setProperty(
                $property->getName(),
                $this->removeIllegalCharacters($this->encodeValue($value)),
                PropertyType::DOUBLE
            );
        } else {
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }
    }

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = $this->defaultValue;
        if ($node->hasProperty($property->getName())) {
            $value = $node->getPropertyValue($property->getName(), PropertyType::DOUBLE);
        }

        $property->setValue($this->decodeValue($value));

        return $value;
    }
}
