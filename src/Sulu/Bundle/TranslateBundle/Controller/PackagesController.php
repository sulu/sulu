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

use Sulu\Bundle\CoreBundle\Controller\Exception\EntityIdAlreadySetException;
use Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException;
use Sulu\Bundle\CoreBundle\Controller\Exception\RestException;
use Sulu\Bundle\CoreBundle\Controller\RestController;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Package;

/**
 * Makes the translation catalogues accessible trough an REST-API
 * @package Sulu\Bundle\TranslateBundle\Controller
 */
class PackagesController extends RestController
{
    protected $entityName = 'SuluTranslateBundle:Package';

    /**
     * Lists all the catalogues or filters the catalogues by parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPackagesAction()
    {
        $view = $this->responseList();

        return $this->handleView($view);
    }

    /**
     * Shows the package with the given Id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPackageAction($id)
    {
        $find = function ($id) {
            return $this->getDoctrine()
                ->getRepository($this->entityName)
                ->find($id);
        };

        $view = $this->responseGetById($id, $find);

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

                    // default value is false
                    $catalogue->setIsDefault(isset($c['isDefault']) ? $c['isDefault'] : false);

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
        /** @var Package $package */
        $package = $this->getDoctrine()
            ->getRepository($this->entityName)
            ->find($id);

        try {
            if (!$package) {
                throw new EntityNotFoundException($this->entityName, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                $name = $this->getRequest()->get('name');

                $package->setName($name);

                if (!$this->processCatalogues($package)) {
                    throw new RestException('Catalogue update not possible', 0);
                }

                $em->flush();
                $view = $this->view($package);
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    protected function processCatalogues(Package $package)
    {
        $catalogues = $this->getRequest()->get('catalogues');

        $delete = function ($catalogue) use ($package) {
            $package->removeCatalogue($catalogue);

            return true;
        };

        $update = function ($catalogue, $entry) {
            return $this->updateCatalogue($catalogue, $entry);
        };

        $add = function ($catalogue) use ($package) {
            return $this->addCatalogue($package, $catalogue);
        };

        return $this->processPut($package->getCatalogues(), $catalogues, $delete, $update, $add);
    }

    protected function addCatalogue(Package $package, $catalogueData)
    {
        $catalogueEntity = 'SuluTranslateBundle:Package';

        $em = $this->getDoctrine()->getManager();

        if (isset($catalogueData['id'])) {
            throw new EntityIdAlreadySetException($catalogueEntity, $catalogueData['id']);
        }

        $catalogue = new Catalogue();
        $catalogue->setLocale($catalogueData['locale']);

        // default value is false
        $catalogue->setIsDefault(isset($catalogueData['isDefault']) ? $catalogueData['isDefault'] : false);

        $catalogue->setPackage($package);
        $package->addCatalogue($catalogue);
        $em->persist($catalogue);

        return true;
    }

    protected function updateCatalogue(Catalogue $catalogue, $entry)
    {
        $catalogue->setLocale($entry['locale']);
        if (isset($catalogueData['isDefault'])) {
            $catalogue->setIsDefault($catalogueData['isDefault']);
        }
        return true;
    }

    /**
     * Deletes the package with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deletePackageAction($id)
    {
        $delete = function ($id) {
            $entityName = 'SuluTranslateBundle:Package';
            $pkg = $this->getDoctrine()
                ->getRepository($entityName)
                ->find($id);

            if (!$pkg) {
                throw new EntityNotFoundException($entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($pkg);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }
}
