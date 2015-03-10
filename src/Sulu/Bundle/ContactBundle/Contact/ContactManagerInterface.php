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
use Sulu\Bundle\ContactBundle\Entity\Address;

/**
 * Defines functionality of ContactManger
 * Interface ContactManagerInterface
 * @package Sulu\Bundle\ContactBundle\Contact
 */
interface ContactManagerInterface
{
    /**
     * adds an address to the entity
     * @param $entity The entity to add the address to
     * @param Address $address The address to be added
     * @param Bool $isMain Defines if the address is the main Address of the contact
     * @return $relation
     */
    public function addAddress($entity, Address $address, $isMain);

    /**
     * removes the address relation from a contact and also deletes the address if it has no more relations
     * @param $entity
     * @param $address
     * @return mixed
     */
    public function removeAddressRelation($entity, $address);

    /**
     * Returns a collection of relations to get addresses
     * @param $entity
     * @return mixed
     */
    public function getAddressRelations($entity);

    /**
     * sets the first element to main, if none is set
     * @param $arrayCollection
     */
    public function setMainForCollection($arrayCollection);

    /**
     * unsets main of all elements of an ArrayCollection | PersistanceCollection
     * @param $arrayCollection
     * @return boolean returns true if a element was unset
     */
    public function unsetMain($arrayCollection);

//    public function addAccountContact($account, $contact);

    /**
     * sets main email, based on emails that are set on entity
     * @param $entity
     * @return mixed
     */
    public function setMainEmail($entity);

    /**
     * sets main fax, based on faxes that are set on entity
     * @param $entity
     * @return mixed
     */
    public function setMainFax($entity);

    /**
     * sets main url, based on urls that are set on entity
     * @param $entity
     * @return mixed
     */
    public function setMainUrl($entity);

    /**
     * sets main phone, based on phones that are set on entity
     * @param $entity
     * @return mixed
     */
    public function setMainPhone($entity);

    /**
     * Returns an api entity
     * @param $id
     * @param $locale
     * @return mixed
     */
    public function getById($id, $locale);
}
