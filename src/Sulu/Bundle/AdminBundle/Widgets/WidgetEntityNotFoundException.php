<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Widgets;

class WidgetEntityNotFoundException extends WidgetException
{
    protected $id;

    public function __construct($message, $widget, $id)
    {
        parent::__construct($message, $widget);
        $this->id = $id;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'widget' => $this->subject,
            'id' => $this->id,
        ];
    }
}
