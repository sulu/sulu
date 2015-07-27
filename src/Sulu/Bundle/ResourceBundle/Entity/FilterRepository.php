<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * FilterRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FilterRepository extends EntityRepository implements FilterRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findByIdAndLocale($id, $locale)
    {
        try {
            $qb = $this->getFilterQuery($locale);
            $qb->andWhere('filter.id = :filterId');
            $qb->setParameter('filterId', $id);

            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $exc) {
            return;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByUserAndContextAndLocale($locale, $context, $userId)
    {
        try {
            $qb = $this->getFilterQuery($locale);
            $qb->leftJoin('filter.user', 'user')
            ->andWhere('filter.context = :context')
            ->orWhere('(user.id = :userId')
            ->orWhere('filter.private = false)')
            ->setParameter('context', $context)
            ->setParameter('userId', $userId);

            return $qb->getQuery()->getResult();
        } catch (NoResultException $exc) {
            return;
        }
    }

    /**
     * Returns the query for filters.
     *
     * @param string $locale The locale to load
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getFilterQuery($locale)
    {
        $qb = $this->createQueryBuilder('filter')
            ->addSelect('conditionGroups')
            ->addSelect('translations')
            ->addSelect('conditions')
            ->leftJoin(
                'filter.translations',
                'translations',
                'WITH',
                'translations.locale = :locale'
            )
            ->leftJoin('filter.conditionGroups', 'conditionGroups')
            ->leftJoin('conditionGroups.conditions', 'conditions')
            ->setParameter('locale', $locale);

        return $qb;
    }

    /**
     * {@inheritDoc}
     */
    public function findById($id)
    {
        try {
            $qb = $this->createQueryBuilder('filter')
                ->andWhere('filter.id = :filterId')
                ->setParameter('filterId', $id);

            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $exc) {
            return;
        }
    }

    /**
     * Deletes multiple filters.
     *
     * @param $ids
     *
     * @return mixed
     */
    public function deleteByIds($ids)
    {
        $qb = $this->createQueryBuilder('filter')->delete()->where(
            'filter.id IN (:ids)'
        )->setParameter('ids', $ids);
        $qb->getQuery()->execute();
    }
}
