<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Filter;

/**
 * Class Types
 * @package Sulu\Bundle\ResourceBundle\Filter
 */
final class DataTypes
{
    /** Types used by operators and conditions */
    const UNDEFINED_TYPE = 0;
    const STRING_TYPE = 1;
    const NUMBER_TYPE = 2;
    const DATETIME_TYPE = 3;

    /**
     * Types constructor.
     */
    private function __construct()
    {
    }
}
