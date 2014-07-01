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

use DateTime;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes collections available through a REST API
 * @package Sulu\Bundle\MediaBundle\Controller
 */
class CollectionController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluMediaBundle:Collection';

    /**
     * {@inheritdoc}
     */
    protected $unsortable = array('lft', 'rgt', 'depth');

    /**
     * {@inheritdoc}
     */
    protected $fieldsDefault = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsExcluded = array('lft', 'rgt', 'depth');

    /**
     * {@inheritdoc}
     */
    protected $fieldsHidden = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsRelations = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(0 => 'id');

    /**
     * {@inheritdoc}
     */
    protected $fieldsTranslationKeys = array('id' => 'public.id');

    /**
     * {@inheritdoc}
     */
    protected $fieldsEditable = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsValidation = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsWidth = array();

    /**
     *
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'media.collection.';

    /**
     * returns all fields that can be used by list
     * @Get("collection/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->responseFields();
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
        $locale = $this->getLocale($request->get('locale'));
        $cm = $this->getCollectionManager();
        $view = $this->responseGetById(
            $id,
            function ($id) use ($locale, $cm) {
                /**
                 * @var CollectionEntity $collectionEntity
                 */
                $collectionEntity = $cm->findById($id);
                return $cm->getApiObject($collectionEntity, $locale);
            }
        );

        return $this->handleView($view);
    }

    /**
     * lists all collections
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $parent = $request->get('parent');
        $depth = $request->get('depth');
        $cm = $this->getCollectionManager();
        $collections = $cm->find($parent, $depth);
        $wrappers = $cm->getApiObjects($collections, $this->getLocale($request->get('locale')));
        $halResponse = $this->createHalResponse($wrappers, true);
        //add children link to hal-links array
        $halResponse['_links']['children'] = $this->replaceOrAddUrlString(
            $halResponse['_links']['self'],
            'parent=', '{parentId}'
        );
        // remove depth parameter from children hal-link
        $halResponse['_links']['children'] = $this->replaceOrAddUrlString(
            $halResponse['_links']['children'],
            'depth=', null
        );
        $view = $this->view($halResponse, 200);
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
            $cm = $this->getCollectionManager();
            $cm->delete($id);
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
            $cm = $this->getCollectionManager();
            $data = $this->getData($request);
            $data['id'] = $id;
            $categoryEntity = $cm->save($data, $this->getUser()->getId());
            $categoryWrapper = $cm->getApiObject($categoryEntity, $this->getLocale($request->get('locale')));

            $view = $this->view($categoryWrapper, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
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
