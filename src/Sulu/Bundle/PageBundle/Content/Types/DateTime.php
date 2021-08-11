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

/**
 * ContentType for DateTime.
 */
class DateTime extends SimpleContentType
{
    public const FORMAT = 'Y-m-d\TH:i:s';

    public function __construct()
    {
        parent::__construct('DateTime');
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
            $value = \DateTime::createFromFormat(static::FORMAT, $value);

            $node->setProperty($property->getName(), $value);
        } else {
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }
    }

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = '';
        if ($node->hasProperty($property->getName())) {
            /** @var \DateTime $propertyValue */
            $propertyValue = $node->getPropertyValue($property->getName());

            if ($propertyValue instanceof \DateTime) {
                $value = $propertyValue->format(static::FORMAT);
            }
        }

        $property->setValue($value);

        return $value;
    }

    public function getContentData(PropertyInterface $property)
    {
        $value = $property->getValue();

        if (!empty($value)) {
            return \DateTime::createFromFormat(static::FORMAT, $value);
        }

        return null;
    }
}
