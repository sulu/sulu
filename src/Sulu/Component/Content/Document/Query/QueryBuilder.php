<?php

namespace Sulu\Component\Content\Document\Query;

use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder as BaseQueryBuilder;

class QueryBuilder extends BaseQueryBuilder
{
    /**
     * @var array
     */
    private $structureMap;

    /**
     * Assign a structure to a document alias.
     *
     * This allows you to use structure fields in criteria as follows:
     *
     * ````
     * $qb->from()->document('foo', 'p');
     * $qb->useStructure('p', 'overview');
     * $qb->where()->eq()->field('p.structure.field1')->literal('barbar');
     * ````
     *
     * @param mixed $documentAlias Document alias to register structure to
     * @param mixed $structureName Name of the structure to register.
     * @throws InvalidArgumentException If a document with the given alias is already registered.
     * @return $this
     */
    public function useStructure($documentAlias, $structureName)
    {
        if (isset($this->structureMap[$documentAlias])) {
            throw new \InvalidArgumentException(sprintf(
                'Structure "%s" is already registered for document alias "%s". Trying to register structure "%s"',
                $this->structureMap[$documentAlias],
                $documentAlias,
                $structureName
            ));
        }

        $this->structureMap[$documentAlias] = $structureName;

        return $this;
    }

    /**
     * Return the document alias to structure name map.
     *
     * @return array
     */
    public function getStructureMap()
    {
        return $this->structureMap;
    }
}
