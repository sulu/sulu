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

    /**
     * The limit for this query
     * @var integer
     */
    private $limit = null;

    /**
     * The page the resulting query will be returning
     * @var integer
     */
    private $page = 1;

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
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrentPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        $qb = $this->em->createQueryBuilder()
            ->select('count(' . $this->entityName . '.id)')
            ->from($this->entityName, $this->entityName);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $qb = $this->em->createQueryBuilder()
            ->select($this->entityName)
            ->from($this->entityName, $this->entityName);

        if ($this->limit != null) {
            $qb->setMaxResults($this->limit);
            $qb->setFirstResult($this->limit * ($this->page - 1));
        }

        return $qb->getQuery()->getResult();
    }
}
