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

class WidgetEntityNotFoundException extends WidgetException
{
    protected $id;

    function __construct($message, $widget, $id)
    {
        parent::__construct($message, $widget);
        $this->id = $id;
    }

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message,
            'widget' => $this->widget,
            'id' => $this->id
        );
    }
}
