<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

use Doctrine\ORM\EntityManager;

/**
 * Factory for DoctrineListBuilders.
 */
class DoctrineListBuilderFactory
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Creates a new DoctrineListBuilder for the given entity name and returns it.
     *
     * @param $entityName
     *
     * @return DoctrineListBuilder
     */
    public function create($entityName)
    {
        return new DoctrineListBuilder($this->em, $entityName);
    }
}
