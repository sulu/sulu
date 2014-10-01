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

class WidgetParameterException extends WidgetException
{
    protected $param;

    function __construct($message, $widget, $param)
    {
        parent::__construct($message, $widget);
        $this->param = $param;
    }

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message,
            'widget' => $this->subject,
            'param' => $this->param
        );
    }
}
