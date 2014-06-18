<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Controller;

use DateTime;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use Sulu\Bundle\CategoryBundle\Api\Category as CategoryWrapper;
use Sulu\Bundle\CategoryBundle\Entity\Category as CategoryEntity;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes categories available through a REST API
 * @package Sulu\Bundle\CategoryBundle\Controller
 */
class CategoryController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluCategoryBundle:Category';

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
    protected $fieldsHidden = array('');

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
    protected $bundlePrefix = 'category.category.';


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
     * Shows a single category with a given id
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        $locale = $this->getLocale($request->get('locale'));
        $view = $this->responseGetById(
            $id,
            function ($id) use ($locale) {
                $categoryEntity = $this->fetchEntity($id);
                return $this->getCategoryWrapper($categoryEntity, $locale);
            }
        );

        return $this->handleView($view);
    }

    /**
     * Shows all categories
     * Can be filtered with "parent" and "depth" parameters
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $parent = $request->get('parent');
        $depth = $request->get('depth');
        $categories = $this->getDoctrine()->getRepository($this->entityName)->findCategories($parent, $depth);
        $wrappers = $this->getCategoryWrappers($categories, $this->getLocale($request->get('locale')));
        $view = $this->view($this->createHalResponse($wrappers), 200);
        return $this->handleView($view);
    }

    /**
     * Adds a new category
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();

            $categoryEntity = new CategoryEntity();
            $categoryEntity->setCreator($this->getUser());
            $categoryEntity->setChanger($this->getUser());
            $categoryEntity->setCreated(new DateTime());
            $categoryEntity->setChanged(new DateTime());

            $categoryWrapper = new CategoryWrapper($categoryEntity, $this->getLocale($request->get('locale')));
            $categoryWrapper->setName($request->get('name'));
            $categoryWrapper->setMeta($request->get('meta'));

            if ($request->get('parent')) {
                $parentEntity = $this->fetchEntity($request->get('parent'));
                $categoryWrapper->setParent($parentEntity);
            }

            $categoryEntity = $categoryWrapper->getEntity();
            $em->persist($categoryEntity);
            $em->flush();

            $view = $this->view($categoryWrapper, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Changes an existing category
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($id, Request $request)
    {
        try {
            if (!$request->get('name')) {
                throw new MissingArgumentException($this->entityName, 'name');
            }
            return $this->changeEntity($id, $request);
        } catch (MissingArgumentException $exc) {
            $view = $this->view($exc->toArray(), 400);
            return $this->handleView($view);
        }
    }

    /**
     * Partly changes an existing category
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction($id, Request $request)
    {
        return $this->changeEntity($id, $request);
    }

    public function deleteAction($id, Request $request)
    {

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
     * Returns the wrapper entity for a given entity or null
     * @param $entity
     * @param $locale
     * @return null|CategoryWrapper
     */
    protected function getCategoryWrapper($entity, $locale)
    {
        if (!$entity) {
            return null;
        } else {
            return new CategoryWrapper($entity, $locale);
        }
    }

    /**
     * Returns an array of CategoryWrappers for a given array of entities
     * @param $entities
     * @param $locale
     * @return null|array
     */
    protected function getCategoryWrappers($entities, $locale)
    {
        $arrReturn = [];
        if ($entities) {
            foreach ($entities as $entity) {
                array_push($arrReturn, new CategoryWrapper($entity, $locale));
            }
        }
        return $arrReturn;
    }

    /**
     * Returns a category entity for a given id
     * @param $id
     * @return CategoryEntity
     */
    protected function fetchEntity($id)
    {
        $entity = $this->getDoctrine()
            ->getRepository($this->entityName)
            ->findCategoryById($id);

        return $entity;
    }

    /**
     * Handles the change of a category. Used in PUT and PATCH
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function changeEntity($id, Request $request)
    {
        try {
            $categoryEntity = $this->fetchEntity($id);
            if (!$categoryEntity) {
                throw new EntityNotFoundException($categoryEntity, $id);
            }

            $em = $this->getDoctrine()->getManager();

            $categoryEntity->setChanged(new DateTime());
            $categoryEntity->setChanger($this->getUser());

            $categoryWrapper = new CategoryWrapper($categoryEntity, $this->getLocale($request->get('locale')));
            $categoryWrapper->setName($request->get('name'));
            $categoryWrapper->setMeta($request->get('meta'));

            $categoryEntity = $categoryWrapper->getEntity();
            $em->persist($categoryEntity);
            $em->flush();

            $view = $this->view($categoryWrapper, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }
}
