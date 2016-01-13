<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Repository;

/**
 * Converts rows into simple data-arrays.
 */
class RowsIterator extends \IteratorIterator
{
    /**
     * @var string[];
     */
    private $columns;

    public function __construct(\Traversable $iterator, array $columns)
    {
        parent::__construct($iterator);

        $this->columns = $columns;
    }

    public function current()
    {
        $row = parent::current();
        $result = [];

        foreach ($this->columns as $column) {
            $result[$column] = $row->getValue($column);
        }

        return $result;
    }
}
