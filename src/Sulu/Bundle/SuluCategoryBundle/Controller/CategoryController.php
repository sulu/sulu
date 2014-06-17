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
use Sulu\Bundle\CategoryBundle\Entity\CategoryMeta;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
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
                $categoryEntity = $this->getDoctrine()
                    ->getRepository($this->entityName)
                    ->findCategoryById($id);

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
        if (!$entities) {
            return null;
        } else {
            $arrReturn = [];
            foreach($entities as $entity) {
                array_push($arrReturn, new CategoryWrapper($entity, $locale));
            }
            return $arrReturn;
        }
    }
}
