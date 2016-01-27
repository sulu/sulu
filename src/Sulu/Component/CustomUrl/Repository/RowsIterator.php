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

use Sulu\Component\Content\Repository\Content;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;

/**
 * Converts rows into simple data-arrays.
 */
class RowsIterator extends \IteratorIterator
{
    /**
     * @var string[];
     */
    private $columns;

    /**
     * @var Content[]
     */
    private $targets;

    /**
     * @var GeneratorInterface
     */
    private $generator;

    public function __construct(\Traversable $iterator, array $columns, array $targets, GeneratorInterface $generator)
    {
        parent::__construct($iterator);

        $this->columns = $columns;
        $this->generator = $generator;

        $this->targets = [];
        foreach ($targets as $target) {
            $this->targets[$target->getId()] = $target;
        }
    }

    public function current()
    {
        $row = parent::current();
        $result = [];

        foreach ($this->columns as $column) {
            $result[str_replace('a.', '', $column)] = $row->getValue($column);
        }

        $result['targetTitle'] = $this->targets[$result['target']]['title'];
        $result['customUrl'] = $this->generator->generate(
            $result['baseDomain'],
            json_decode($result['domainParts'], true)
        );

        return $result;
    }
}
