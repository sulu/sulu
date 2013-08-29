<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\CoreBundle\Controller\Exception\EntityIdAlreadySetException;
use Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException;
use Sulu\Bundle\CoreBundle\Controller\Exception\RestException;
use Sulu\Bundle\CoreBundle\Controller\RestController;
use \DateTime;

/**
 * Makes accounts available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class AccountsController extends RestController implements ClassResourceInterface
{
    protected $entityName = 'SuluContactBundle:Account';

    /**
     * Shows a single account with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        return $this->responseGetById(
            $id,
            function ($id) {
                return $this->getDoctrine()
                    ->getRepository($this->entityName)
                    ->find($id);
            }
        );
    }

    /**
     * Lists all the accounts or filters the accounts by parameters
     * Special function for lists
     * route /contacts/list
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction()
    {
        return $this->responseList();
    }

    /**
     * Creates a new account
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        $name = $this->getRequest()->get('name');

        if ($name != null) {
            $em = $this->getDoctrine()->getManager();

            try {
                $account = new Account();

                $account->setName($this->getRequest()->get('name'));
                //FIXME set correct values
                $account->setLft(0);
                $account->setRgt(0);
                $account->setDepth(0);

                $account->setCreated(new DateTime());
                $account->setChanged(new DateTime());

                $urls = $this->getRequest()->get('urls');
                if (!empty($urls)) {
                    foreach ($urls as $urlData) {
                        $this->addUrl($account, $urlData);
                    }
                }

                $emails = $this->getRequest()->get('emails');
                if (!empty($emails)) {
                    foreach ($emails as $emailData) {
                        $this->addEmail($account, $emailData);
                    }
                }

                $phones = $this->getRequest()->get('phones');
                if (!empty($phones)) {
                    foreach ($phones as $phoneData) {
                        $this->addPhone($account, $phoneData);
                    }
                }

                $addresses = $this->getRequest()->get('addresses');
                if (!empty($addresses)) {
                    foreach ($addresses as $addressData) {
                        $this->addAddress($account, $addressData);
                    }
                }

                $notes = $this->getRequest()->get('notes');
                if (!empty($notes)) {
                    foreach ($notes as $noteData) {
                        $this->addNote($account, $noteData);
                    }
                }

                $em->persist($account);

                $em->flush();
                $view = $this->view($account, 200);
            } catch (RestException $exc) {
                $view = $this->view($exc->toArray(), 400);
            }
        } else {
            $view = $this->view(null, 400);
        }

        return $this->handleView($view);
    }

    /**
     * Adds URL to an account
     * @param Account $account
     * @param $urlData
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityIdAlreadySetException
     */
    private function addUrl(Account $account, $urlData)
    {
        $em = $this->getDoctrine()->getManager();
        $urlEntity = 'SuluContactBundle:Url';
        $urlTypeEntity = 'SuluContactBundle:UrlType';

        $urlType = $this->getDoctrine()
            ->getRepository($urlTypeEntity)
            ->find($urlData['urlType']['id']);

        if (isset($urlData['id'])) {
            throw new EntityIdAlreadySetException($urlEntity, $urlData['id']);
        } elseif (!$urlType) {
            throw new EntityNotFoundException($urlTypeEntity, $urlData['urlType']['id']);
        } else {
            $url = new Url();
            $url->setUrl($urlData['url']);
            $url->setUrlType($urlType);
            $em->persist($url);
            $account->addUrl($url);
        }
    }

    /**
     * Adds an email address to an account
     * @param Account $account
     * @param $emailData
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityIdAlreadySetException
     */
    private function addEmail(Account $account, $emailData)
    {
        $em = $this->getDoctrine()->getManager();
        $emailEntity = 'SuluContactBundle:Email';
        $emailTypeEntity = 'SuluContactBundle:EmailType';

        $urlType = $this->getDoctrine()
            ->getRepository($emailTypeEntity)
            ->find($emailData['emailType']['id']);

        if (isset($emailData['id'])) {
            throw new EntityIdAlreadySetException($emailEntity, $emailData['id']);
        } elseif (!$urlType) {
            throw new EntityNotFoundException($emailTypeEntity, $emailData['emailType']['id']);
        } else {
            $email = new Email();
            $email->setEmail($emailData['email']);
            $email->setEmailType($urlType);
            $em->persist($email);
            $account->addEmail($email);
        }
    }

    /**
     * Adds a phone number to an account
     * @param Account $account
     * @param $phoneData
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityIdAlreadySetException
     */
    private function addPhone(Account $account, $phoneData)
    {
        $em = $this->getDoctrine()->getManager();
        $phoneTypeEntity = 'SuluContactBundle:PhoneType';

        $phoneType = $this->getDoctrine()
            ->getRepository($phoneTypeEntity)
            ->find($phoneData['phoneType']['id']);

        if (isset($phoneData['id'])) {
            throw new EntityIdAlreadySetException($phoneTypeEntity, $phoneData['id']);
        } elseif (!$phoneType) {
            throw new EntityNotFoundException($phoneTypeEntity, $phoneData['phoneType']['id']);
        } else {
            $url = new Phone();
            $url->setPhone($phoneData['phone']);
            $url->setPhoneType($phoneType);
            $em->persist($url);
            $account->addPhone($url);
        }
    }

    /**
     * Adds an address to an account
     * @param Account $account
     * @param $addressData
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityIdAlreadySetException
     */
    private function addAddress(Account $account, $addressData)
    {
        $em = $this->getDoctrine()->getManager();
        $addressEntity = 'SuluContactBundle:Address';
        $addressTypeEntity = 'SuluContactBundle:AddressType';
        $countryEntity = 'SuluContactBundle:Country';

        $addressType = $this->getDoctrine()
            ->getRepository($addressTypeEntity)
            ->find($addressData['addressType']['id']);

        $country = $this->getDoctrine()
            ->getRepository($countryEntity)
            ->find($addressData['country']['id']);

        if (isset($addressData['id'])) {
            throw new EntityIdAlreadySetException($addressEntity, $addressData['id']);
        } elseif (!$country) {
            throw new EntityNotFoundException($countryEntity, $addressData['country']['id']);
        } elseif (!$addressType) {
            throw new EntityNotFoundException($addressTypeEntity, $addressData['addressType']['id']);
        } else {
            $address = new Address();
            $address->setStreet($addressData['street']);
            $address->setNumber($addressData['number']);
            $address->setZip($addressData['zip']);
            $address->setCity($addressData['city']);
            $address->setState($addressData['state']);
            $address->setCountry($country);
            $address->setAddressType($addressType);

            // add additional fields
            if (isset($addressData['addition'])) {
                $address->setAddition($addressData['addition']);
            }

            $em->persist($address);

            $account->addAddresse($address);
        }
    }

    /**
     * Add a new note to the given contact and persist it with the given object manager
     * @param Account $account
     * @param $noteData
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityIdAlreadySetException
     */
    protected function addNote(Account $account, $noteData)
    {
        $em = $this->getDoctrine()->getManager();
        $noteEntity = 'SuluContactBundle:Note';

        if (isset($noteData['id'])) {
            throw new EntityIdAlreadySetException($noteEntity, $noteData['id']);
        } else {
            $note = new Note();
            $note->setValue($noteData['value']);

            $em->persist($note);
            $account->addNote($note);
        }
    }
}