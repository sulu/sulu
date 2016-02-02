<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Controller;

use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Package;
use Sulu\Bundle\TranslateBundle\Translate\TranslateCollectionRepresentation;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes the translation catalogues accessible trough an REST-API.
 */
class PackageController extends RestController implements ClassResourceInterface
{
    protected static $entityName = 'SuluTranslateBundle:Package';

    protected static $entityKey = 'packages';

    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors = [];

    protected $basePath = 'admin/api/contacts';

    protected $unsortable = [];

    protected $fieldsDefault = ['name'];
    protected $fieldsExcluded = [];
    protected $fieldsHidden = [];
    protected $fieldsRelations = [];
    protected $fieldsSortOrder = [0 => 'id'];
    protected $fieldsTranslationKeys = [];
    protected $bundlePrefix = 'translate.package.';

    /**
     * returns all fields that can be used by list.
     *
     * @Get("packages/fields")
     *
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->handleView($this->view(array_values($this->getFieldDescriptors())));
    }

    /**
     * persists a setting.
     *
     * @Put("packages/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }

    /**
     * Lists all the catalogues or filters the catalogues by parameters.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        if ($request->get('flat') == 'true') {
            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create(self::$entityName);

            $restHelper->initializeListBuilder($listBuilder, $this->getFieldDescriptors());

            $list = new ListRepresentation(
                $listBuilder->execute(),
                self::$entityKey,
                'get_packages',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $list = new TranslateCollectionRepresentation(
                $this->getDoctrine()->getRepository(self::$entityName)->findAll(),
                self::$entityKey
            );
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Shows the package with the given Id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $find = function ($id) {
            return $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->getPackageById($id);
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }

    /**
     * Creates a new catalogue.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        $name = $request->get('name');

        if ($name != null) {
            $em = $this->getDoctrine()->getManager();

            $catalogues = $request->get('catalogues');

            $package = new Package();
            $package->setName($name);

            if ($catalogues != null) {
                foreach ($catalogues as $catalogueData) {
                    $catalogue = new Catalogue();
                    $catalogue->setLocale($catalogueData['locale']);

                    // default value is false
                    $catalogue->setIsDefault(isset($catalogueData['isDefault']) ? $catalogueData['isDefault'] : false);

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
     *
     * @param Request $request
     * @param int     $id      The id of the package to update
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $id)
    {
        /** @var Package $package */
        $package = $this->getDoctrine()
            ->getRepository(self::$entityName)
            ->getPackageById($id);

        try {
            if (!$package) {
                throw new EntityNotFoundException(self::$entityName, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                $name = $request->get('name');

                $package->setName($name);

                $catalogues = $request->get('catalogues', []);
                if (!$this->processCatalogues($catalogues, $package)) {
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

    protected function processCatalogues($catalogues, Package $package)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $delete = function ($catalogue) use ($package, $em) {
            $package->removeCatalogue($catalogue);
            $em->remove($catalogue);

            return true;
        };

        $update = function ($catalogue, $entry) {
            return $this->updateCatalogue($catalogue, $entry);
        };

        $add = function ($catalogue) use ($package) {
            return $this->addCatalogue($package, $catalogue);
        };

        $get = function ($catalogue) {
            return $catalogue->getId();
        };

        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');

        return $restHelper->processSubEntities($package->getCatalogues(), $catalogues, $get, $add, $update, $delete);
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
        $em->persist($package);

        return true;
    }

    protected function updateCatalogue(Catalogue $catalogue, $catalogueData)
    {
        $catalogue->setLocale($catalogueData['locale']);

        if (isset($catalogueData['isDefault'])) {
            $catalogue->setIsDefault($catalogueData['isDefault']);
        }

        return true;
    }

    /**
     * Deletes the package with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $entityName = 'SuluTranslateBundle:Package';
            $package = $this->getDoctrine()
                ->getRepository($entityName)
                ->getPackageById($id);

            if (!$package) {
                throw new EntityNotFoundException($entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($package);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * @return DoctrineFieldDescriptor[]
     */
    private function getFieldDescriptors()
    {
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor('id', 'id', self::$entityName, 'id', []);
        $this->fieldDescriptors['name'] = new DoctrineFieldDescriptor('name', 'name', self::$entityName, 'name', []);

        return $this->fieldDescriptors;
    }

    /**
     * @param $key
     *
     * @return DoctrineFieldDescriptor
     */
    private function getFieldDescriptor($key)
    {
        return $this->fieldDescriptors[$key];
    }
}
