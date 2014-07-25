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
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\CategoryBundle\Category\CategoryListRepresentation;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\RestHelperInterface;

/**
 * Makes categories available through a REST API
 * @package Sulu\Bundle\CategoryBundle\Controller
 */
class CategoryController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $entityName = 'SuluCategoryBundle:Category';

    /**
     * {@inheritdoc}
     */
    protected static $entityKey = 'categories';

    /**
     * {@inheritdoc}
     */
    protected $sortable = array('name', 'created', 'changed');

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
    protected $fieldsHidden = array('id', 'created', 'changed');

    /**
     * {@inheritdoc}
     */
    protected $fieldsRelations = array('name');

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(0 => 'id', 1 => 'name');

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
     * @Get("categories/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        $cm = $this->get('sulu_category.category_manager');
        $cm->createFieldDescriptors();

        // default contacts list
        return $this->handleView($this->view(array_values($cm->getFieldDescriptors()), 200));
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
        $cm = $this->get('sulu_category.category_manager');
        $view = $this->responseGetById(
            $id,
            function ($id) use ($locale, $cm) {
                $categoryEntity = $cm->findById($id);
                return $cm->getApiObject($categoryEntity, $locale);
            }
        );

        return $this->handleView($view);
    }

    /**
     * Shows all categories
     * Can be filtered with "parent" and "depth" parameters
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\MissingArgumentException
     */
    public function cgetAction(Request $request)
    {
        $parent = $request->get('parent');
        $depth = $request->get('depth');
        $sortBy = $request->get('sortBy');
        $sortOrder = $request->get('sortOrder');
        $ids = $request->get('ids');

        $cm = $this->get('sulu_category.category_manager');

        if ($request->get('flat') == 'true') {

            $listRestHelper = $this->get('sulu_core.list_rest_helper');

            $categories = $cm->find($ids, $parent, $depth, $sortBy, $sortOrder);
            $wrappers = $cm->getApiObjects($categories, $this->getLocale($request->get('locale')));

            $all = count($wrappers); // TODO

            $list = new CategoryListRepresentation(
                $wrappers,
                self::$entityKey,
                'get_categories',
                $request->query->all(),
                $listRestHelper->getPage(),
                $listRestHelper->getLimit(),
                $all
            );

        } else {
            $categories = $cm->find($ids, $parent, $depth, $sortBy, $sortOrder);
            $wrappers = $cm->getApiObjects($categories, $this->getLocale($request->get('locale')));
            $list = new CollectionRepresentation($wrappers, self::$entityKey);
        }

        $view = $this->view($list, 200);
        return $this->handleView($view);
    }

    /**
     * Adds a new category
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        return $this->saveEntity(null, $request);
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
                throw new MissingArgumentException(self::$entityName, 'name');
            }
            return $this->saveEntity($id, $request);
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
        return $this->saveEntity($id, $request);
    }

    /**
     * Deletes a category with a given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $cm = $this->get('sulu_category.category_manager');
            $cm->delete($id);
        };

        $view = $this->responseDelete($id, $delete);

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
     * Handles the change of a category. Used in PUT and PATCH
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function saveEntity($id, Request $request)
    {
        try {
            $cm = $this->get('sulu_category.category_manager');
            $data = [
                'id' => $id,
                'name' => $request->get('name'),
                'meta' => $request->get('meta'),
                'parent' => $request->get('parent'),
                'locale' => $this->getLocale($request->get('locale'))
            ];
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
}
