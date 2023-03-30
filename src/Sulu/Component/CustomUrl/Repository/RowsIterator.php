<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Repository;

use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;

/**
 * Converts rows into simple data-arrays.
 */
class RowsIterator extends \IteratorIterator
{
    /**
     * @var string[]
     */
    private array $columns;

    /**
     * @var Content[]
     */
    private array $targets;

    private \Sulu\Component\CustomUrl\Generator\GeneratorInterface $generator;

    private \Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface $userManager;

    public function __construct(
        \Traversable $iterator,
        array $columns,
        array $targets,
        GeneratorInterface $generator,
        UserManagerInterface $userManager
    ) {
        parent::__construct($iterator);

        $this->columns = $columns;
        $this->generator = $generator;
        $this->userManager = $userManager;

        $this->targets = [];
        foreach ($targets as $target) {
            $this->targets[$target->getId()] = $target;
        }
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $row = parent::current();
        $result = [];

        foreach ($this->columns as $column) {
            if ('uuid' === $column) {
                $result['id'] = $row->getValue($column);
                continue;
            }

            $result[\str_replace('a.', '', $column)] = $row->getValue($column);
        }

        $result['targetTitle'] = '';
        if (!empty($result['targetDocument']) && \array_key_exists($result['targetDocument'], $this->targets)) {
            $result['targetTitle'] = $this->targets[$result['targetDocument']]['title'];
        }
        $result['domainParts'] = \json_decode($result['domainParts'], true);
        $result['customUrl'] = $this->generator->generate(
            $result['baseDomain'],
            $result['domainParts']
        );

        $result['creatorFullName'] = $this->userManager->getFullNameByUserId($result['creator']);
        $result['changerFullName'] = $this->userManager->getFullNameByUserId($result['changer']);

        return $result;
    }
}
