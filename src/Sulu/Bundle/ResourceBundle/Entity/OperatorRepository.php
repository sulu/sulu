<?php

/*
 * This file is part of Sulu.
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
 * Repository for operators
 * Class OperatorRepository.
 */
class OperatorRepository extends EntityRepository implements OperatorRepositoryInterface
{
    /**
     * Searches for all operator by locale.
     *
     * @param $locale
     *
     * @return mixed
     */
    public function findAllByLocale($locale)
    {
        try {
            return $this->getOperatorQuery($locale)->getQuery()->getResult();
        } catch (NoResultException $exc) {
            return;
        }
    }

    /**
     * Returns the query for operators.
     *
     * @param string $locale The locale to load
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getOperatorQuery($locale)
    {
        $qb = $this->createQueryBuilder('operator')
            ->addSelect('operatorValues')
            ->addSelect('translations')
            ->addSelect('operatorValueTranslations')
            ->leftJoin('operator.translations', 'translations', 'WITH', 'translations.locale = :locale')
            ->leftJoin('operator.values', 'operatorValues')
            ->leftJoin(
                'operatorValues.translations',
                'operatorValueTranslations',
                'WITH',
                'operatorValueTranslations.locale = :locale'
            )
            ->setParameter('locale', $locale);

        return $qb;
    }
}
