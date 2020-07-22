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

use Sulu\Component\Content\SimpleContentType;

class SegmentSelect extends SimpleContentType
{
    public function __construct()
    {
        parent::__construct('SegmentSelect', '{}');
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

        return \json_decode($value);
    }
}
