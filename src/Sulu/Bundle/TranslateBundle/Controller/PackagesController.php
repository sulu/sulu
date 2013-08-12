<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * Lists all the catalogues or filters the catalogues by parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPackagesAction()
    {
        $response = array();

        $listHelper = $this->get('sulu_core.list_rest_helper');

        /** @var array $packages */
        $packages = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Package')
            ->findBy(
                array(),
                $listHelper->getSorting(),
                $listHelper->getLimit(),
                $listHelper->getOffset()
            );

        $response['total'] = count($packages);

        $fields = $listHelper->getFields();
        if ($fields != null) {
            // Walk through all packages and only extract informations from given fields
            foreach ($packages as $package) {
                /** @var Package $package */
                $item = array();
                if (in_array('name', $fields)) {
                    $item['name'] = $package->getName();
                }
                if (in_array('id', $fields)) {
                    $item['id'] = $package->getId();
                }
                $response['items'][] = $item;
            }
        } else {
            $response['items'] = $packages;
        }

        $view = $this->view($response, 200);

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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postPackagesAction()
    {
        $name = $this->getRequest()->get('name');

        if ($name != null) {
            $em = $this->getDoctrine()->getManager();

            $catalogues = $this->getRequest()->get('catalogues');

            $package = new Package();
            $package->setName($name);

            if ($catalogues != null) {
                foreach ($catalogues as $c) {
                    $catalogue = new Catalogue();
                    $catalogue->setLocale($c['locale']);
                    $catalogue->setPackage($package);
                    $package->addCatalogue($catalogue);
                    $em->persist($catalogue);
                }
            }

            $em->persist($package);
            $em->flush();

            $view = $this->view($package, 200);
        } else {
            $view = $this->view(null, 400);
        }

        return $this->handleView($view);
    }

    /**
     * Update the existing package or create a new one with the given id,
     * if the package with the given id is not yet existing.
     * @param integer $id The id of the package to update
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putPackagesAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $error = false;

        $name = $this->getRequest()->get('name');
        $languages = $this->getRequest()->get('languages');
        $catalogues = $this->getRequest()->get('catalogues');

        /** @var Package $package */
        $package = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Package')
            ->find($id);

        if (!$package) {
            $view = $this->view(null, 400);
        } else {
            $package->setName($name);
            $packageCatalogues = $package->getCatalogues();

            if ($catalogues != null) {
                // Go through each existing catalogue and update/delete
                foreach ($packageCatalogues as $packageCatalogue) {
                    /** @var Catalogue $packageCatalogue */
                    $packageCatalogueId = $packageCatalogue->getId();

                    $matchedEntry = null;
                    $matchedKey = null;
                    foreach ($catalogues as $key => $catalogue) {
                        if (isset($catalogue['id']) && $catalogue['id'] == $packageCatalogueId) {
                            $matchedEntry = $catalogue;
                            $matchedKey = $key;
                        }
                    }

                    if ($matchedEntry == null) {
                        // If entry is not listed anymore delete it
                        $package->removeCatalogue($packageCatalogue);
                    } else {
                        // If entry matched a request parameter update catalogue
                        $packageCatalogue->setLocale($matchedEntry['locale']);
                        $packageCatalogue->setPackage($package);
                    }

                    // Remove done element from array
                    if (!is_null($matchedKey)) {
                        unset($catalogues[$matchedKey]);
                    }
                }
                // Go through the rest of the catalogues, and add them
                foreach ($catalogues as $catalogue) {
                    /** @var Catalogue $catalogue */
                    if (isset($catalogue['id'])) {
                        // There should not be an ID
                        $view = $this->view(null, 400);
                        $error = true;
                        break;
                    }
                    $packageCatalogue = new Catalogue();
                    $packageCatalogue->setLocale($catalogue['locale']);
                    $packageCatalogue->setPackage($package);
                    $package->addCatalogue($packageCatalogue);
                    $em->persist($packageCatalogue);
                }
            }

            if (!$error) {
                $em->flush();
                $view = $this->view($package);
            }
        }

        return $this->handleView($view);
    }
}
