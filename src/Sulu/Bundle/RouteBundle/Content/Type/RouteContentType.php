<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Content\Type;

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\SimpleContentType;

/**
 * Provides simple route edit.
 */
class RouteContentType extends SimpleContentType
{
    public function __construct()
    {
        parent::__construct('Route', '');
    }

    public function getDefaultParams(?PropertyInterface $property = null)
    {
        return [
            'inputType' => new PropertyParameter('inputType', 'full'),
        ];
    }
}
