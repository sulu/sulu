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

use Exception;

class WidgetException extends Exception
{
    protected $widget;

    function __construct($message, $widget)
    {
        parent::__construct($message);
        $this->widget = $widget;
    }

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message,
            'widget' => $this->widget
        );
    }
}
