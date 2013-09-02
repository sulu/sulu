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

    /**
     * Returns the form for accounts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function accountFormAction()
    {
        $values = $this->getValues();
        $defaults = $this->getDefaults();

        return $this->render('SuluContactBundle:Template:account.form.html.twig', array(
                'addressTypes' => $values['addressTypes'],
                'phoneTypes' => $values['phoneTypes'],
                'emailTypes' => $values['emailTypes'],
                'countries' => $values['countries'],
                'defaultPhoneType' => $defaults['phoneType'],
                'defaultEmailType' => $defaults['emailType'],
                'defaultAddressType' => $defaults['addressType']
            ));
    }

    /**
     * Returns the possible values for the dropdowns
     * @return array
     */
    private function getValues()
    {
        $values = array();

        $emailTypeEntity = 'SuluContactBundle:EmailType';
        $values['emailTypes'] = $this->getDoctrine($emailTypeEntity)
            ->getRepository($emailTypeEntity)
            ->findAll();

        $phoneTypeEntity = 'SuluContactBundle:PhoneType';
        $values['phoneTypes'] = $this->getDoctrine()
            ->getRepository($phoneTypeEntity)
            ->findAll();

        $addressTypeEntity = 'SuluContactBundle:AddressType';
        $values['addressTypes'] = $this->getDoctrine()
            ->getRepository($addressTypeEntity)
            ->findAll();

        $values['countries'] = $this->getDoctrine()
            ->getRepository('SuluContactBundle:Country')
            ->findAll();

        return $values;
    }

    /**
     * Returns the default values for the dropdowns
     * @return array
     */
    private function getDefaults()
    {
        $config = $this->container->getParameter('sulu_contact.defaults');
        $defaults = array();

        $emailTypeEntity = 'SuluContactBundle:EmailType';
        $defaults['emailType'] = $this->getDoctrine($emailTypeEntity)
            ->getRepository($emailTypeEntity)
            ->find($config['emailType']);

        $phoneTypeEntity = 'SuluContactBundle:PhoneType';
        $defaults['phoneType'] = $this->getDoctrine()
            ->getRepository($phoneTypeEntity)
            ->find($config['phoneType']);

        $addressTypeEntity = 'SuluContactBundle:AddressType';
        $defaults['addressType'] = $this->getDoctrine()
            ->getRepository($addressTypeEntity)
            ->find($config['addressType']);

        return $defaults;
    }
}
