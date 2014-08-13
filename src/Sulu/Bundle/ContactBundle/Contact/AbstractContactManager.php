<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * This Manager handles general Account and Contact functionality
 * Class AbstractContactManager
 * @package Sulu\Bundle\ContactBundle\Contact
 */
abstract class AbstractContactManager implements ContactManagerInterface
{
    /**
     * @var ObjectManager $em
     */
    public $em;

    /**
     * @param ObjectManager $em
     */
    public function __construct(ObjectManager $em) {
        $this->em = $em;
    }

    /**
     * Returns an api entity
     * @param $id
     * @param $locale
     * @return mixed
     */
    abstract protected function getById($id, $locale);

    /**
     * Returns all api entities
     * @param $locale
     * @return mixed
     */
    abstract protected function getAll($locale);

    /**
     * unsets main of all elements of an ArrayCollection | PersistanceCollection
     * @param $arrayCollection
     * @return boolean returns true if a element was unset
     */
    public function unsetMain($arrayCollection)
    {
        if ($arrayCollection && !$arrayCollection->isEmpty()) {
            return $arrayCollection->forAll(
                function ($index, $entry) {
                    if ($entry->getMain() === true) {
                        $entry->setMain(false);
                        return false;
                    }
                    return true;
                }
            );
        }
    }

    /**
     * sets the first element to main, if none is set
     * @param $arrayCollection
     */
    public function setMainForCollection($arrayCollection)
    {
        if ($arrayCollection && !$arrayCollection->isEmpty() && !$this->hasMain($arrayCollection)) {
            $arrayCollection->first()->setMain(true);
        }
    }

    /**
     * checks if a collection for main attribute
     * @param $arrayCollection
     * @param $mainEntity will be set, if found
     * @return mixed
     */
    private function hasMain($arrayCollection, &$mainEntity = null)
    {
        if ($arrayCollection && !$arrayCollection->isEmpty()) {
            return $arrayCollection->exists(function ($index, $entity) {
                $mainEntity = $entity;
                return $entity->getMain() === true;
            });
        }
        return false;
    }

    /**
     * sets Entity's Main-Email
     * @param Contact|Account $entity
     */
    public function setMainEmail($entity)
    {
        // set main to first entry or to null
        if ($entity->getEmails()->isEmpty()) {
            $entity->setMainEmail(null);
        } else {
            $entity->setMainEmail($entity->getEmails()->first()->getEmail());
        }
    }

    /**
     * sets Entity's Main-Phone
     * @param Contact|Account $entity
     */
    public function setMainPhone($entity)
    {
        // set main to first entry or to null
        if ($entity->getPhones()->isEmpty()) {
            $entity->setMainPhone(null);
        } else {
            $entity->setMainPhone($entity->getPhones()->first()->getPhone());
        }
    }

    /**
     * sets Entity's Main-Fax
     * @param Contact|Account $entity
     */
    public function setMainFax($entity)
    {
        // set main to first entry or to null
        if ($entity->getFaxes()->isEmpty()) {
            $entity->setMainFax(null);
        } else {
            $entity->setMainFax($entity->getFaxes()->first()->getFax());
        }
    }

    /**
     * sets Entity's Main-Url
     * @param Contact|Account $entity
     */
    public function setMainUrl($entity)
    {
        // set main to first entry or to null
        if ($entity->getUrls()->isEmpty()) {
            $entity->setMainUrl(null);
        } else {
            $entity->setMainUrl($entity->getUrls()->first()->getUrl());
        }
    }
}
