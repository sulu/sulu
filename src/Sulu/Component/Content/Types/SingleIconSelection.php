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

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

class SingleIconSelection extends SimpleContentType
{
    public function __construct()
    {
        parent::__construct('single_icon_selection');
    }

    public function getContentData(PropertyInterface $property)
    {
        $content = [
            'icon' => $property->getValue(),
        ];

        return $content;
    }

    public function getViewData(PropertyInterface $property)
    {
        $params = $property->getParams();

        $view = [
            'iconSet' => $params['icon_set']->getValue(),
        ];

        return $view;
    }
}
