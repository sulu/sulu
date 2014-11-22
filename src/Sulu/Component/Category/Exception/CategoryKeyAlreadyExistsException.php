<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Category\Exception;

class CategoryKeyAlreadyExistsException extends CategoryException
{
    public function __construct()
    {
        parent::__construct('The given category key already exits!', CategoryExceptions::CODE_KEY_ALREADY_EXITS);
    }
}
