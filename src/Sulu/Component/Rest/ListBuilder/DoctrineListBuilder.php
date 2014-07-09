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

use Doctrine\ORM\EntityManager;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor\DoctrineFieldDescriptor;

class DoctrineListBuilder extends AbstractListBuilder
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
     * @var DoctrineFieldDescriptor[]
     */
    protected $fields = array();

    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $searchFields = array();

    /**
     * @var DoctrineFieldDescriptor
     */
    protected $sortField;

    public function __construct(EntityManager $em, $entityName)
    {
        $this->em = $em;
        $this->entityName = $entityName;
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
            ->from($this->entityName, $this->entityName);

        foreach ($this->fields as $field) {
            $qb->addSelect($field->getFullName() . ' AS ' . $field->getAlias());
        }

        foreach ($this->getJoins() as $entity => $join) {
            $qb->leftJoin($join, $entity);
        }

        if ($this->search != null) {
            foreach ($this->searchFields as $searchField) {
                $qb->orWhere($searchField->getFullName() . ' LIKE :search');
            }

            $qb->setParameter('search', '%' . $this->search . '%');
        }

        if ($this->sortField != null) {
            $qb->orderBy($this->sortField->getFullName(), $this->sortOrder);
        }

        if ($this->limit != null) {
            $qb->setMaxResults($this->limit)->setFirstResult($this->limit * ($this->page - 1));
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Returns all the joins required for the query
     * @return array
     */
    private function getJoins()
    {
        $joins = array();

        if ($this->sortField != null) {
            $joins = array_merge($joins, $this->sortField->getJoins());
        }

        foreach ($this->fields as $field) {
            $joins = array_merge($joins, $field->getJoins());
        }

        foreach ($this->searchFields as $field) {
            $joins = array_merge($joins, $field->getJoins());
        }

        return $joins;
    }
}
