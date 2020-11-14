<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Sulu\Bundle\ContactBundle\Entity\Address;

/**
 * Defines functionality of ContactManger.
 */
interface ContactManagerInterface
{
    /**
     * adds an address to the entity.
     *
     * @param ContactInterface $entity The entity to add the address to
     * @param Address $address The address to be added
     * @param bool $isMain Defines if the address is the main Address of the contact
     *
     * @return Array $relation
     */
    public function addAddress($entity, Address $address, $isMain);

    /**
     * removes the address relation from a contact and also deletes the address if it has no more relations.
     *
     * @param ContactInterface $entity
     * @param Address $address
     */
    public function removeAddressRelation($entity, $address);

    /**
     * Returns a collection of relations to get addresses.
     *
     * @param string $entity
     */
    public function getAddressRelations($entity);

    /**
     * sets the first element to main, if none is set.
     *
     * @param array $arrayCollection
     */
    public function setMainForCollection($arrayCollection);

    /**
     * unsets main of all elements of an ArrayCollection | PersistanceCollection.
     *
     * @param array $arrayCollection
     *
     * @return bool returns true if a element was unset
     */
    public function unsetMain($arrayCollection);

    /**
     * sets main email, based on emails that are set on entity.
     *
     * @param string $entity
     */
    public function setMainEmail($entity);

    /**
     * sets main fax, based on faxes that are set on entity.
     *
     * @param string $entity
     */
    public function setMainFax($entity);

    /**
     * sets main url, based on urls that are set on entity.
     *
     * @param string $entity
     */
    public function setMainUrl($entity);

    /**
     * sets main phone, based on phones that are set on entity.
     *
     * @param string $entity
     */
    public function setMainPhone($entity);

    /**
     * Returns an api entity.
     *
     * @param string $id
     * @param string $locale
     */
    public function getById($id, $locale);

    /**
     * Returns api entities.
     *
     * @param array $ids
     * @param string $locale
     */
    public function getByIds($ids, $locale);
}
