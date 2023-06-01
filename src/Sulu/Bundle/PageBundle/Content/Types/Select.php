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
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for a multiple select. Currently only support for checkboxes.
 */
class Select extends SimpleContentType
{
    public function __construct()
    {
        parent::__construct('MultipleSelect', []);
    }

    public function getDefaultParams(?PropertyInterface $property = null)
    {
        return [
            'values' => new PropertyParameter('values', [], 'collection'),
        ];
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
        $property->setValue(\json_decode($value, true));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }
}
