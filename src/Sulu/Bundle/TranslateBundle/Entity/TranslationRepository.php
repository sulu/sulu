<?php

namespace Sulu\Bundle\TranslateBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for the Translations, implementing some additional functions
 * for querying objects
 */
class TranslationRepository extends EntityRepository
{
    public function findFiltered($packageId, $locale)
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT t
                FROM SuluTranslateBundle:Translation t
                JOIN t.catalogue ca
                JOIN ca.package p
                JOIN t.code co
                WHERE ca.locale = :locale
                    AND p.id = :packageId
            ')
            ->setParameters(array(
                    'packageId' => $packageId,
                    'locale' => $locale
                ))
            ->getResult();
    }
}
