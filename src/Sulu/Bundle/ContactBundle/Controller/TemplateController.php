<?php

namespace Sulu\Bundle\ContactBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{
	public function contactFormAction()
	{
		$emailTypes = $this->getDoctrine()
			->getRepository('SuluContactBundle:EmailType')
			->findAll();

		$phoneTypes = $this->getDoctrine()
			->getRepository('SuluContactBundle:PhoneType')
			->findAll();

		$addressTypes = $this->getDoctrine()
			->getRepository('SuluContactBundle:AddressType')
			->findAll();

		$countries = $this->getDoctrine()
			->getRepository('SuluContactBundle:Country')
			->findAll();

		return $this->render('SuluContactBundle:Template:contact.form.html.twig', array(
			'addressTypes' => $addressTypes,
			'phoneTypes' => $phoneTypes,
			'emailTypes' => $emailTypes,
			'countries' => $countries
		));
	}

}
