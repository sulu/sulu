<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource;

/**
 * Datatypes used by filters and operators to generate proper queries
 * Class Types.
 */
final class DataTypes
{
    /** Types used by operators and conditions */
    public const UNDEFINED_TYPE = 0;

    public const STRING_TYPE = 1;

    public const NUMBER_TYPE = 2;

    public const DATETIME_TYPE = 3;

    public const BOOLEAN_TYPE = 4;

    public const TAGS_TYPE = 5;

    public const AUTO_COMPLETE_TYPE = 6;

    /**
     * Types constructor.
     */
    private function __construct()
    {
    }
}
