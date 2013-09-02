<?php

namespace Sulu\Bundle\ContactBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{
	public function contactFormAction()
	{
		$defaults = $this->container->getParameter('sulu_contact.defaults');

		$emailTypeEntity = 'SuluContactBundle:EmailType';
		$emailTypes = $this->getDoctrine($emailTypeEntity)
			->getRepository($emailTypeEntity)
			->findAll();
		$defaultEmailType = $this->getDoctrine($emailTypeEntity)
			->getRepository($emailTypeEntity)
			->find($defaults['emailType']);

		$phoneTypeEntity = 'SuluContactBundle:PhoneType';
		$phoneTypes = $this->getDoctrine()
			->getRepository($phoneTypeEntity)
			->findAll();
		$defaultPhoneType = $this->getDoctrine()
			->getRepository($phoneTypeEntity)
			->find($defaults['phoneType']);

		$addressTypeEntity = 'SuluContactBundle:AddressType';
		$addressTypes = $this->getDoctrine()
			->getRepository($addressTypeEntity)
			->findAll();
		$defaultAddressType = $this->getDoctrine()
			->getRepository($addressTypeEntity)
			->find($defaults['addressType']);

		$countryEntity = 'SuluContactBundle:Country';
		$countries = $this->getDoctrine()
			->getRepository($countryEntity)
			->findAll();
		$defaultCountry = $this->getDoctrine()
			->getRepository($countryEntity)
			->find($defaults['country']);

		return $this->render('SuluContactBundle:Template:contact.form.html.twig', array(
			'addressTypes' => $addressTypes,
			'phoneTypes' => $phoneTypes,
			'emailTypes' => $emailTypes,
			'countries' => $countries,
			'defaultPhoneType' => $defaultPhoneType,
			'defaultEmailType' => $defaultEmailType,
			'defaultAddressType' => $defaultAddressType,
			'defaultCountry' => $defaultCountry
		));
	}

}
