<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource\Exception;

/**
 * This exception is thrown if a required property for creating or manipulating
 * a filter is missing.
 */
class MissingFilterException extends FilterException
{
    /**
     * The name of the filter which is missing.
     *
     * @var string
     */
    private $filter;

    public function __construct($filter)
    {
        $this->filter = $filter;
        parent::__construct('The filter with the name "' . $this->filter . '" is missing.', 0);
    }

    /**
     * Returns the name of the missing filter.
     *
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }
}
