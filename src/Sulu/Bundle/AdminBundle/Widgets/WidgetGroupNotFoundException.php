<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Widgets;

use Sulu\Bundle\AdminBundle\Widgets\WidgetException;

class WidgetGroupNotFoundException extends WidgetException
{
    function __construct($message, $group)
    {
        parent::__construct($message, $group);
    }

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message,
            'group' => $this->subject
        );
    }
}
