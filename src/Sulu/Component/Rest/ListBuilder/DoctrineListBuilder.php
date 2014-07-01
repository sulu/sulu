<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor\DoctrineFieldDescriptor;

class DoctrineListBuilder implements ListBuilderInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * The name of the entity to build the list for
     * @var string
     */
    private $entityName;

    /**
     * The field descriptors for the current list
     * @var DoctrineFieldDescriptor
     */
    private $fields = array();

    public function __construct(EntityManager $em, $entityName)
    {
        $this->em = $em;
        $this->entityName = $entityName;
    }

    /**
     * {@inheritDoc}
     */
    public function add($fieldDescriptor)
    {
        $this->fields[] = $fieldDescriptor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function sortBy($fieldDescriptor)
    {
        // TODO: Implement sortBy() method.
    }

    /**
     * {@inheritDoc}
     */
    public function sortOrder($order)
    {
        // TODO: Implement sortOrder() method.
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $query = $this->em->createQueryBuilder()
            ->select($this->entityName)
            ->from($this->entityName, $this->entityName)
            ->getQuery();

        return $query->getResult();
    }
}
