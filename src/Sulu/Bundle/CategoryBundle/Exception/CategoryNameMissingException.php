<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Exception;

/**
 * An instance of this exception signals that a category has no set name.
 */
class CategoryNameMissingException extends \Exception
{
    /**
     * CategoryNameMissingException constructor.
     */
    public function __construct()
    {
        parent::__construct('A category cannot be saved without a name.');
    }
}
