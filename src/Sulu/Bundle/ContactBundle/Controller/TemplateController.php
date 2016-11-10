<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use Sulu\Component\Rest\RestController;

class TemplateController extends RestController
{
    /**
     * Returns Template for contact list.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contactListAction()
    {
        return $this->render('SuluContactBundle:Template:contact.list.html.twig', $this->getContactListData());
    }

    /**
     * Returns Template for account form contact list.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function accountFormContactListAction()
    {
        return $this->render('SuluContactBundle:Template:account.form.contact.html.twig', $this->getContactListData());
    }

    /**
     * Returns Template for account list.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function accountListAction()
    {
        return $this->render('SuluContactBundle:Template:account.list.html.twig');
    }

    /**
     * Returns the form for contacts.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contactFormAction()
    {
        $data = $this->getRenderArray();
        $data['form_of_address'] = [];

        foreach ($this->container->getParameter('sulu_contact.form_of_address') as $el) {
            $data['form_of_address'][] = $el;
        }

        $categoryRoot = $this->container->getParameter('sulu_contact.contact_form.category_root');
        $data['categoryUrl'] = $this->getCategoryUrl($categoryRoot);

        return $this->render('SuluContactBundle:Template:contact.form.html.twig', $data);
    }

    /**
     * Returns the form for accounts.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function accountFormAction()
    {
        $categoryRoot = $this->container->getParameter('sulu_contact.account_form.category_root');

        return $this->render(
            'SuluContactBundle:Template:account.form.html.twig',
            array_merge(['categoryUrl' => $this->getCategoryUrl($categoryRoot)], $this->getRenderArray())
        );
    }

    private function getCategoryUrl($key)
    {
        return $this->generateUrl(
            'get_categories',
            ['flat' => 'true', 'rootKey' => $key, 'sortBy' => 'name', 'sortOrder' => 'asc']
        );
    }

    /**
     * Returns the template for account- and contact-documents.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function basicDocumentsAction()
    {
        return $this->render('SuluContactBundle:Template:basic.documents.html.twig');
    }

    /**
     * Returns an array for rendering a form.
     *
     * @return array
     */
    private function getRenderArray()
    {
        $values = $this->getValues();
        $defaults = $this->getDefaults();

        return [
            'addressTypes' => $values['addressTypes'],
            'phoneTypes' => $values['phoneTypes'],
            'emailTypes' => $values['emailTypes'],
            'urlTypes' => $values['urlTypes'],
            'faxTypes' => $values['faxTypes'],
            'countries' => $values['countries'],
            'defaultPhoneType' => $defaults['phoneType'],
            'defaultEmailType' => $defaults['emailType'],
            'defaultAddressType' => $defaults['addressType'],
            'defaultUrlType' => $defaults['urlType'],
            'defaultFaxType' => $defaults['faxType'],
            'defaultCountry' => $defaults['country'],
        ];
    }

    /**
     * Returns the possible values for the dropdowns.
     *
     * @return array
     */
    private function getValues()
    {
        $values = [];

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

        $values['urlTypes'] = $this->getDoctrine()
            ->getRepository('SuluContactBundle:UrlType')
            ->findAll();

        $values['faxTypes'] = $this->getDoctrine()
            ->getRepository('SuluContactBundle:FaxType')
            ->findAll();

        $values['countries'] = $this->getDoctrine()
            ->getRepository('SuluContactBundle:Country')
            ->findAll();

        return $values;
    }

    /**
     * Returns the default values for the dropdowns.
     *
     * @return array
     */
    private function getDefaults()
    {
        $config = $this->container->getParameter('sulu_contact.defaults');
        $defaults = [];

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

        $urlTypeEntity = 'SuluContactBundle:UrlType';
        $defaults['urlType'] = $this->getDoctrine()
            ->getRepository($urlTypeEntity)
            ->find($config['urlType']);

        $faxTypeEntity = 'SuluContactBundle:FaxType';
        $defaults['faxType'] = $this->getDoctrine()
            ->getRepository($faxTypeEntity)
            ->find($config['faxType']);

        $countryEntity = 'SuluContactBundle:Country';
        $defaults['country'] = $this->getDoctrine()
            ->getRepository($countryEntity)
            ->findOneByCode($config['country']);

        return $defaults;
    }

    /**
     * Returns data to render contact list.
     *
     * @return array
     */
    private function getContactListData()
    {
        $data['form_of_address'] = [];
        foreach ($this->container->getParameter('sulu_contact.form_of_address') as $el) {
            $data['form_of_address'][] = $el;
        }

        $emailTypeEntity = 'SuluContactBundle:EmailType';
        $data['email_types'] = $this->getDoctrine($emailTypeEntity)
            ->getRepository($emailTypeEntity)
            ->findAll();

        return $data;
    }
}
