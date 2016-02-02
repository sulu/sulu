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

use FOS\RestBundle\Controller\Annotations\Get;
use Sulu\Bundle\TranslateBundle\Translate\TranslateCollectionRepresentation;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Make the catalogues available through a REST-API.
 */
class CatalogueController extends RestController
{
    protected static $entityName = 'SuluTranslateBundle:Catalogue';

    protected static $entityKey = 'catalogues';

    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors = [];

    /**
     * returns all fields that can be used by list.
     *
     * @Get("catalogues/fields")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function getFieldsAction(Request $request)
    {
        $fieldDescriptors = array_values($this->getFieldDescriptors($request->getLocale()));

        return $this->handleView($this->view($fieldDescriptors, 200));
    }

    /**
     * Returns the catalogue with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCatalogueAction($id)
    {
        $find = function ($id) {
            return $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->getCatalogueById($id);
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }

    /**
     * Returns a list of catalogues (from a specific package).
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetCataloguesAction(Request $request)
    {
        $filter = [];

        $packageId = $request->get('packageId');
        if (!empty($packageId)) {
            $filter['packageId'] = $packageId;
        }

        if ($request->get('flat') == 'true') {
            /** @var RestHelperInterface $restHelper */
           $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create(self::$entityName);

            $restHelper->initializeListBuilder($listBuilder, $this->getFieldDescriptors());

            foreach ($filter as $key => $value) {
                $listBuilder->where($this->getFieldDescriptor($key), $value);
            }

            $list = new ListRepresentation(
                $listBuilder->execute(),
                self::$entityKey,
                'get_catalogues',
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
     * @return DoctrineFieldDescriptor[]
     */
    protected function getFieldDescriptors()
    {
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor('id', 'id', self::$entityName, 'id', [], true, false, '', '50px');
        $this->fieldDescriptors['locale'] = new DoctrineFieldDescriptor('locale', 'locale', self::$entityName, 'locale', [], true, false, '', '50px');
        $this->fieldDescriptors['packageId'] = new DoctrineFieldDescriptor('package', 'packageId', self::$entityName, 'package', [], true, false, '', '50px');
        $this->fieldDescriptors['isDefault'] = new DoctrineFieldDescriptor('isDefault', 'isDefault', self::$entityName, 'default');

        return $this->fieldDescriptors;
    }

    /**
     * @param $key
     *
     * @return DoctrineFieldDescriptor
     */
    protected function getFieldDescriptor($key)
    {
        return $this->fieldDescriptors[$key];
    }

    /**
     * Deletes the catalogue with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteCataloguesAction($id)
    {
        $delete = function ($id) {
            $catalogue = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->getCatalogueById($id);

            if (!$catalogue) {
                throw new EntityNotFoundException(self::$entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($catalogue);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }
}
