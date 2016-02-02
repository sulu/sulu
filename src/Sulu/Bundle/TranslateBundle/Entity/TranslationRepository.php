<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for the Translations, implementing some additional functions
 * for querying objects.
 */
class TranslationRepository extends EntityRepository
{
    /**
     * returns translation with given code and catalogue.
     *
     * @param $codeId
     * @param $catalogueId
     */
    public function getTranslation($codeId, $catalogueId)
    {
        $dql = 'SELECT tr
				FROM SuluTranslateBundle:Translation tr
					JOIN tr.catalogue ca
					JOIN tr.code co
				WHERE co.id = :codeId AND
					  ca.id = :catalogueId';

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters(
                [
                    'codeId' => $codeId,
                    'catalogueId' => $catalogueId,
                ]
            );

        $result = $query->getResult();
        if (count($result) == 1) {
            return $result[0];
        } else {
            return;
        }
    }

    /**
     * find translation with a few filters.
     *
     * @param $locale
     * @param null $backend
     * @param null $frontend
     * @param null $location
     * @param null $packageId
     *
     * @return array
     */
    public function findFiltered($locale, $backend = null, $frontend = null, $location = null, $packageId = null)
    {
        $dql = 'SELECT tr
                    FROM SuluTranslateBundle:Translation tr
                        JOIN tr.catalogue ca
                        JOIN ca.package pa
                        JOIN tr.code co
                        LEFT JOIN co.location lo
                    WHERE ca.locale = :locale';

        // add additional conditions, if they are set
        if ($backend != null) {
            $dql .= '
                      AND co.backend = :backend';
        }

        if ($frontend != null) {
            $dql .= '
                      AND co.frontend = :frontend';
        }

        if ($location != null) {
            $dql .= '
                      AND lo.name = :location';
        }

        if ($packageId != null) {
            $dql .= '
                      AND pa.id = :packageId';
        }

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters(
                [
                    'locale' => $locale,
                ]
            );

        // set the additional parameter, if they are set
        if ($backend != null) {
            $query->setParameter('backend', $backend);
        }

        if ($frontend != null) {
            $query->setParameter('frontend', $frontend);
        }

        if ($location != null) {
            $query->setParameter('location', $location);
        }

        if ($packageId != null) {
            $query->setParameter('packageId', $packageId);
        }

        return $query->getResult();
    }
}
