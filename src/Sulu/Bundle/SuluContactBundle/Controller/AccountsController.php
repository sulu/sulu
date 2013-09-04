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
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
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
		$view = $this->responseGetById(
			$id,
			function ($id) {
				return $this->getDoctrine()
					->getRepository($this->entityName)
					->find($id);
			}
		);

        return $this->handleView($view);
	}

	/**
	 * Lists all the accounts or filters the accounts by parameters
	 * Special function for lists
	 * route /contacts/list
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function listAction()
	{
		$view = $this->responseList();

        return $this->handleView($view);
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

				$parentData = $this->getRequest()->get('parent');
				if ($parentData != null && isset($parentData['id'])) {
					$parent = $this->getDoctrine()
						->getRepository($this->entityName)
						->find($parentData['id']);

					if (!$parent) {
						throw new EntityNotFoundException($this->entityName, $parentData['id']);
					}
					$account->setParent($parent);
				}

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
	 * Edits the existing contact with the given id
	 * @param integer $id The id of the contact to update
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException
	 */
	public function putAction($id)
	{
		$accountEntity = 'SuluContactBundle:Account';
		/** @var Account $account */
		$account = $this->getDoctrine()
			->getRepository($accountEntity)
			->find($id);

		if (!$account) {
			$exc = new EntityNotFoundException($accountEntity, $id);
			$view = $this->view($exc->toArray(), 400);
		} else {
			try {
				$em = $this->getDoctrine()->getManager();

				$account->setName($this->getRequest()->get('name'));

				$parentData = $this->getRequest()->get('parent');
				if ($parentData != null && isset($parentData['id'])) {
					$parent = $this->getDoctrine()
						->getRepository($this->entityName)
						->find($parentData['id']);

					if (!$parent) {
						throw new EntityNotFoundException($this->entityName, $parentData['id']);
					}
					$account->setParent($parent);
				}

				$account->setChanged(new DateTime());

				// process details
				$success = $this->processUrls($account)
					&& $this->processEmails($account)
					&& $this->processPhones($account)
					&& $this->processAddresses($account)
					&& $this->processNotes($account);

				if ($success) {
					$em->flush();
					$view = $this->view($account, 200);
				} else {
					$view = $this->view(null, 400);
				}
			} catch (RestException $exc) {
				$view = $this->view($exc->toArray(), 400);
			}
		}

		return $this->handleView($view);
	}

	/**
	 * Delete an account with the given id
	 * @param $id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction($id)
	{
		$account = $this->getDoctrine()
			->getRepository('SuluContactBundle:Account')
			->find($id);

		if ($account != null) {
			$em = $this->getDoctrine()->getManager();
			$em->remove($account);
			$em->flush();

			$view = $this->view(null, 204);

		} else {
			$view = $this->view(null, 404);

		}

		return $this->handleView($view);
	}

	/**
	 * Process all urls from request
	 * @param Account $account The contact on which is worked
	 * @return bool True if the processing was sucessful, otherwise false
	 */
	protected function processUrls(Account $account)
	{
		$urls = $this->getRequest()->get('urls');

		$delete = function ($url) use ($account) {
			$account->removeUrl($url);

			return true;
		};

		$update = function ($url, $matchedEntry) {
			return $this->updateUrl($url, $matchedEntry);
		};

		$add = function ($url) use ($account) {
			$this->addUrl($account, $url);

			return true;
		};

		return $this->processPut($account->getUrls(), $urls, $delete, $update, $add);
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
	 * Updates the given url address
	 * @param Url $url The email object to update
	 * @param string $entry The entry with the new data
	 * @return bool True if successful, otherwise false
	 */
	protected function updateUrl(Url $url, $entry)
	{
		$success = true;
		$urlTypeEntity = 'SuluContactBundle:UrlType';

		/** @var UrlType $urlType */
		$urlType = $this->getDoctrine()
			->getRepository($urlTypeEntity)
			->find($entry['urlType']['id']);

		if (!$urlType) {
			throw new EntityNotFoundException($urlTypeEntity, $entry['urlType']['id']);
		} else {
			$url->setUrl($entry['url']);
			$url->setUrlType($urlType);
		}

		return $success;
	}

	/**
	 * Process all emails from request
	 * @param Account $account The contact on which is worked
	 * @return bool True if the processing was sucessful, otherwise false
	 */
	protected function processEmails(Account $account)
	{
		$emails = $this->getRequest()->get('emails');

		$delete = function ($email) use ($account) {
			$account->removeEmail($email);

			return true;
		};

		$update = function ($email, $matchedEntry) {
			return $this->updateEmail($email, $matchedEntry);
		};

		$add = function ($email) use ($account) {
			$this->addEmail($account, $email);

			return true;
		};

		return $this->processPut($account->getEmails(), $emails, $delete, $update, $add);
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
	 * Updates the given email address
	 * @param Email $email The email object to update
	 * @param string $entry The entry with the new data
	 * @return bool True if successful, otherwise false
	 * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException
	 */
	protected function updateEmail(Email $email, $entry)
	{
		$success = true;
		$emailTypeEntity = 'SuluContactBundle:EmailType';

		$emailType = $this->getDoctrine()
			->getRepository($emailTypeEntity)
			->find($entry['emailType']['id']);

		if (!$emailType) {
			throw new EntityNotFoundException($emailTypeEntity, $entry['emailType']['id']);
		} else {
			$email->setEmail($entry['email']);
			$email->setEmailType($emailType);
		}

		return $success;
	}

	/**
	 * Process all phones from request
	 * @param Account $account The contact on which is worked
	 * @return bool True if the processing was sucessful, otherwise false
	 */
	protected function processPhones(Account $account)
	{
		$phones = $this->getRequest()->get('phones');

		$delete = function ($phone) use ($account) {
			$account->removePhone($phone);

			return true;
		};

		$update = function ($phone, $matchedEntry) {
			return $this->updatePhone($phone, $matchedEntry);
		};

		$add = function ($phone) use ($account) {
			return $this->addPhone($account, $phone);
		};

		return $this->processPut($account->getPhones(), $phones, $delete, $update, $add);
	}

	/**
	 * Adds a phone number to an account
	 * @param Account $account
	 * @param $phoneData
	 * @return bool
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

		return true;
	}

	/**
	 * Updates the given phone
	 * @param Phone $phone The phone object to update
	 * @param string $entry The entry with the new data
	 * @return bool True if successful, otherwise false
	 * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException
	 */
	protected function updatePhone(Phone $phone, $entry)
	{
		$success = true;
		$phoneTypeEntity = 'SuluContactBundle:PhoneType';

		$phoneType = $this->getDoctrine()
			->getRepository($phoneTypeEntity)
			->find($entry['phoneType']['id']);

		if (!$phoneType) {
			throw new EntityNotFoundException($phoneTypeEntity, $entry['phoneType']['id']);
		} else {
			$phone->setPhone($entry['phone']);
			$phone->setPhoneType($phoneType);
		}

		return $success;
	}


	/**
	 * Process all addresses from request
	 * @param Account $account The contact on which is worked
	 * @return bool True if the processing was sucessful, otherwise false
	 */
	protected function processAddresses(Account $account)
	{
		$addresses = $this->getRequest()->get('addresses');

		$delete = function ($address) use ($account) {
			$account->removeAddresse($address);

			return true;
		};

		$update = function ($address, $matchedEntry) {
			return $this->updateAddress($address, $matchedEntry);
		};

		$add = function ($address) use ($account) {
			$this->addAddress($account, $address);

			return true;
		};

		return $this->processPut($account->getAddresses(), $addresses, $delete, $update, $add);
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
	 * Updates the given address
	 * @param Address $address The phone object to update
	 * @param mixed $entry The entry with the new data
	 * @return bool True if successful, otherwise false
	 * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException
	 */
	protected function updateAddress(Address $address, $entry)
	{
		$success = true;
		$addressTypeEntity = 'SuluContactBundle:AddressType';
		$countryEntity = 'SuluContactBundle:Country';

		$addressType = $this->getDoctrine()
			->getRepository($addressTypeEntity)
			->find($entry['addressType']['id']);

		$country = $this->getDoctrine()
			->getRepository($countryEntity)
			->find($entry['country']['id']);

		if (!$addressType) {
			throw new EntityNotFoundException($addressTypeEntity, $entry['addressType']['id']);
		} else if (!$country) {
			throw new EntityNotFoundException($countryEntity, $entry['country']['id']);
		} else {
			$address->setStreet($entry['street']);
			$address->setNumber($entry['number']);
			$address->setZip($entry['zip']);
			$address->setCity($entry['city']);
			$address->setState($entry['state']);
			$address->setCountry($country);
			$address->setAddressType($addressType);

			if (isset($entry['addition'])) {
				$address->setAddition($entry['addition']);
			}
		}

		return $success;
	}

	/**
	 * Process all notes from request
	 * @param Account $account The contact on which is worked
	 * @return bool True if the processing was sucessful, otherwise false
	 */
	protected function processNotes(Account $account)
	{
		$notes = $this->getRequest()->get('notes');

		$delete = function ($note) use ($account) {
			$account->removeNote($note);

			return true;
		};

		$update = function ($note, $matchedEntry) {
			return $this->updateNote($note, $matchedEntry);
		};

		$add = function ($note) use ($account) {
			return $this->addNote($account, $note);
		};

		return $this->processPut($account->getNotes(), $notes, $delete, $update, $add);
	}

	/**
	 * Add a new note to the given contact and persist it with the given object manager
	 * @param Account $account
	 * @param $noteData
	 * @return bool
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

		return true;
	}

	/**
	 * Updates the given note
	 * @param Note $note The phone object to update
	 * @param string $entry The entry with the new data
	 * @return bool True if successful, otherwise false
	 */
	protected function updateNote(Note $note, $entry)
	{
		$success = true;

		$note->setValue($entry['value']);

		return $success;
	}

    /**
     * Returns information about referenced data which will be deleted also
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDeleteinfoAction($id)
    {
        $response = array();
        $response['contacts'] = array();

        /** @var Account $account */
        $account = $this->getDoctrine()
            ->getRepository('SuluContactBundle:Account')
            ->find($id);

        if ($account != null) {

            $completeContacts = $account->getContacts();

            foreach($completeContacts as $c) {
                $contact = array();
                $contact['id'] = $c->getId();
                $contact['firstName'] = $c->getFirstName();
                $contact['middleName'] = $c->getMiddleName();
                $contact['lastName'] = $c->getLastName();

                $response['contacts'][] = $contact;
            }

            $view = $this->view($response, 200);

        } else {

            $view = $this->view(null, 404);

        }
        return $this->handleView($view);
    }

}
