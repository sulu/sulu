<?php

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;

/**
 * BaseTypeRepository
 *
 * Acts as base repository for contact types like email, phone, fax, address or url
 */
abstract class BaseTypeRepository extends EntityRepository
{
    public function findOneByName($name, $scrict = false)
    {
        try {
            if ($scrict === true) {
                $comparator = '=';
            } else {
                $comparator = 'LIKE';
                $name = '%' . $name . '%';
            }

            $qb = $this->createQueryBuilder('entity')
                ->where('entity.name ' . $comparator . ' :name')
                ->setParameter('name', $name);

            $query = $qb->getQuery();
            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return null;
        } catch (NonUniqueResultException $nure) {
            return null;
        }
    }
}
