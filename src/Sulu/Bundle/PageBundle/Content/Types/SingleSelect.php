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

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for single selects. Currently only supports radios.
 */
class SingleSelect extends SimpleContentType
{
    public function __construct()
    {
        parent::__construct('SingleSelect');
    }

    public function getDefaultParams(?PropertyInterface $property = null)
    {
        return [
            'values' => new PropertyParameter('values', [], 'collection'),
        ];
    }
}
