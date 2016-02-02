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

use Exception;

class WidgetException extends Exception
{
    protected $subject;

    public function __construct($message, $subject)
    {
        parent::__construct($message);
        $this->subject = $subject;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'widget' => $this->subject,
        ];
    }
}
