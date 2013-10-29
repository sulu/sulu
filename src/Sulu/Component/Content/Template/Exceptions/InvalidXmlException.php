<?php

namespace Sulu\Component\Content\Template\Exceptions;
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

use Exception;

class InvalidXmlException extends Exception
{
    public function __construct($message = "")
    {
        $msg = "The given XML is invalid!";
        parent::__construct($msg +" "+$message);
    }
}

