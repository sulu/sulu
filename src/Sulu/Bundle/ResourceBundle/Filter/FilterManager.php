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

/**
 * Class FilterManager
 * @package Sulu\Bundle\ResourceBundle\Filter
 */
class FilterManager implements FilterManagerInterface
{

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

    public function getFieldDescriptors($getLocale)
    {
        // TODO: Implement getFieldDescriptors() method.
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
}
