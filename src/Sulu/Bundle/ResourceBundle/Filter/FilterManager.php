<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Filter;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ResourceBundle\Api\Filter;
use Sulu\Bundle\ResourceBundle\Entity\FilterRepositoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;

/**
 * Class FilterManager
 * @package Sulu\Bundle\ResourceBundle\Filter
 */
class FilterManager implements FilterManagerInterface
{
    protected static $filterEntityName = 'SuluResourceBundle:Filter';
    protected static $filterTranslationEntityName = 'SuluResourceBundle:FilterTranslation';
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var FilterRepositoryInterface
     */
    protected $filterRepository;

    public function __construct($em, FilterRepositoryInterface $filterRepo)
    {
        $this->em = $em;
        $this->filterRepository = $filterRepo;
    }

    /**
     * Returns an array of field descriptors
     *
     * @param $locale
     * @return array
     */
    public function getFieldDescriptors($locale)
    {
        $fieldDescriptors = array();
        $fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$filterEntityName,
            'public.id',
            array(),
            true
        );
        $fieldDescriptors['name'] = new DoctrineFieldDescriptor(
            'name',
            'name',
            self::$filterTranslationEntityName,
            'resource.filter.name',
            array(
                self::$filterTranslationEntityName => new DoctrineJoinDescriptor(
                    self::$filterTranslationEntityName,
                    self::$filterEntityName.'.translations',
                    self::$filterTranslationEntityName.'.locale = \''.$locale.'\''
                ),
            )
        );
        $fieldDescriptors['andCombination'] = new DoctrineFieldDescriptor(
            'andCombination',
            'andCombination',
            self::$filterEntityName,
            'resource.filter.andCombination',
            array(),
            true
        );
        $fieldDescriptors['entityName'] = new DoctrineFieldDescriptor(
            'entityName',
            'entityName',
            self::$filterEntityName,
            'resource.filter.entityName',
            array(),
            true
        );

        return $fieldDescriptors;
    }

    /**
     * Finds a filter by id and locale
     *
     * @param $id
     * @param $locale
     * @return Filter
     */
    public function findByIdAndLocale($id, $locale)
    {
        $filter = $this->filterRepository->findByIdAndLocale($id, $locale);

        return new Filter($filter, $locale);
    }

    public function findAllByLocale($locale)
    {
        $filters = $this->filterRepository->findAllByLocale($locale);
        array_walk(
            $filters,
            function (&$filter) use ($locale) {
                $filter = new Filter($filter, $locale);
            }
        );

        return $filters;
    }
}
