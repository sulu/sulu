<?php

namespace Sulu\Bundle\TranslateBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for the Translations, implementing some additional functions
 * for querying objects
 */
class TranslationRepository extends EntityRepository
{
    public function findFiltered($packageId, $locale, $backend = null, $frontend = null, $location = null)
    {
        $dql = '
                SELECT t
                FROM SuluTranslateBundle:Translation t
                JOIN t.catalogue ca
                JOIN ca.package p
                JOIN t.code co
                LEFT JOIN co.location l
                WHERE ca.locale = :locale
                    AND p.id = :packageId
            ';

        // add additional conditions if backend or frontend is set
        if ($backend != null) {
            $dql .= '
                AND co.backend = :backend
            ';
        }

        if( $frontend != null) {
            $dql .= '
                AND co.frontend = :frontend
            ';
        }

        if ($location != null) {
            $dql .= '
                AND l.name = :location
            ';
        }

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters(array(
                    'packageId' => $packageId,
                    'locale' => $locale
                )
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

        return $query->getResult();
    }
}
