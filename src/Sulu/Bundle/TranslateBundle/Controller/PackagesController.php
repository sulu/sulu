<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Package;

/**
 * Makes the translation catalogues accessible trough an REST-API
 * @package Sulu\Bundle\TranslateBundle\Controller
 */
class PackagesController extends FOSRestController
{
    /**
     * Lists all the catalogues
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPackagesAction()
    {
        $packages = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Package')
            ->findAll();

        $view = $this->view($packages, 200);

        return $this->handleView($view);
    }

    /**
     * Shows the package with the given Id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPackageAction($id)
    {
        $package = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Package')
            ->find($id);

        $view = $this->view($package, 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new catalogue
     */
    public function postPackagesAction()
    {
        $em = $this->getDoctrine()->getManager();

        $languages = $this->getRequest()->get('languages');

        $package = new Package();
        $package->setName($this->getRequest()->get('name'));

        if ($languages != null) {
            foreach ($languages as $language) {
                $catalogue = new Catalogue();
                $catalogue->setCode($language);
                $catalogue->setPackage($package);
                $package->addCatalogue($catalogue);
                $em->persist($catalogue);
            }
        }

        $em->persist($package);
        $em->flush();

        $view = $this->view($package, 200);

        return $this->handleView($view);
    }
}
