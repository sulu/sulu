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

class WidgetParameterException extends WidgetException
{
    protected $param;

    public function __construct($message, $widget, $param)
    {
        parent::__construct($message, $widget);
        $this->param = $param;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'widget' => $this->subject,
            'param' => $this->param,
        ];
    }
}
