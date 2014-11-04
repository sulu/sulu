<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use SebastianBergmann\Exporter\Exception;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes collections available through a REST API
 * @package Sulu\Bundle\MediaBundle\Controller
 */
class CollectionController extends RestController implements ClassResourceInterface
{
    /**
     * @var string
     */
    protected static $entityName = 'SuluMediaBundle:Collection';

    /**
     * @var string
     */
    protected static $entityKey = 'collections';

    /**
     * returns all fields that can be used by list
     * @Get("collection/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->getCollectionManager()->getFieldDescriptors();
    }

    /**
     * persists a setting
     * @Put("collection/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }

    /**
     * Shows a single collection with the given id
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        try {
            $locale = $this->getLocale($request->get('locale'));
            $collectionManager = $this->getCollectionManager();
            $view = $this->responseGetById(
                $id,
                function ($id) use ($locale, $collectionManager) {
                    /** @var CollectionEntity $collectionEntity */

                    return $collectionManager->getById($id, $locale);
                }
            );
        } catch (CollectionNotFoundException $cnf) {
            $view = $this->view($cnf->toArray(), 404);
        } catch (MediaException $me) {
            $view = $this->view($me->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * lists all collections
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        try {
            /** @var ListRestHelperInterface $listRestHelper */
            $listRestHelper = $this->get('sulu_core.list_rest_helper');

            $parent = $request->get('parent');
            $depth = $request->get('depth');
            $limit = $request->get('limit', $listRestHelper->getLimit());
            $offset = ($request->get('page', 1) - 1) * $limit;
            $search = $request->get('search');
            $collectionManager = $this->getCollectionManager();

            $collections = $collectionManager->get($this->getLocale($request->get('locale')), array(
                'parent' => $parent,
                'depth' => $depth,
                'search' => $search,
            ), $limit, $offset);

            $all = $collectionManager->getCount();

            $list = new ListRepresentation(
                $collections,
                self::$entityKey,
                'get_collections',
                $request->query->all(),
                $listRestHelper->getPage(),
                $listRestHelper->getLimit(),
                $all
            );

            $view = $this->view($list, 200);
        } catch (CollectionNotFoundException $cnf) {
            $view = $this->view($cnf->toArray(), 404);
        } catch (MediaException $me) {
            $view = $this->view($me->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Creates a new collection
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        return $this->saveEntity(null, $request);
    }

    /**
     * Edits the existing collection with the given id
     * @param integer $id The id of the collection to update
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        return $this->saveEntity($id, $request);
    }

    /**
     * Delete a collection with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            try {
                $collectionManager = $this->getCollectionManager();
                $collectionManager->delete($id);
            } catch (CollectionNotFoundException $cnf) {
                throw new EntityNotFoundException(self::$entityName, $id); // will throw 404 Entity not found
            } catch (MediaException $me) {
                throw new RestException($me->getMessage(), $me->getCode()); // will throw 400 Bad Request
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     * @return Collection
     */
    protected function getData(Request $request)
    {
        return array(
            'style' => $request->get('style'),
            'type' => $request->get('type', $this->container->getParameter('sulu_media.collection.type.default')),
            'parent' => $request->get('parent'),
            'locale' => $request->get('locale', $this->getLocale($request->get('locale'))),
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'changer' => $request->get('changer'),
            'creator' => $request->get('creator'),
            'changed' => $request->get('changed'),
            'created' => $request->get('created'),
        );
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function saveEntity($id, Request $request)
    {
        try {
            $collectionManager = $this->getCollectionManager();
            $data = $this->getData($request);
            $data['id'] = $id;
            $collection = $collectionManager->save($data, $this->getUser()->getId());

            $view = $this->view($collection, 200);
        } catch (CollectionNotFoundException $cnf) {
            $view = $this->view($cnf->toArray(), 404);
        } catch (MediaException $me) {
            $view = $this->view($me->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @param $requestLocale
     * @return mixed
     */
    protected function getLocale($requestLocale)
    {
        if ($requestLocale) {
            return $requestLocale;
        }

        return $this->getUser()->getLocale();
    }

    /**
     * @return CollectionManagerInterface
     */
    protected function getCollectionManager()
    {
        return $this->get('sulu_media.collection_manager');
    }
}
