<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\CategoryBundle\Category\CategoryListRepresentation;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Exception\CategoryIdNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotUniqueException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes categories available through a REST API.
 */
class CategoryController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    /**
     * {@inheritdoc}
     */
    protected static $entityName = CategoryInterface::class;

    /**
     * {@inheritdoc}
     */
    protected static $entityKey = 'categories';

    /**
     * Returns all fields that can be used by list.
     *
     * @Get("categories/fields")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function getFieldsAction(Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $fieldDescriptors = $this->getCategoryManager()->getFieldDescriptors($locale);

        // unset list-irrelevant field descriptors
        unset($fieldDescriptors['lft']);
        unset($fieldDescriptors['rgt']);
        unset($fieldDescriptors['depth']);
        unset($fieldDescriptors['parent']);
        unset($fieldDescriptors['hasChildren']);
        unset($fieldDescriptors['locale']);
        unset($fieldDescriptors['defaultLocale']);

        return $this->handleView($this->view(array_values($fieldDescriptors), 200));
    }

    /**
     * Returns the category which is assigned to the given id.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $findCallback = function ($id) use ($locale) {
            $entity = $this->getCategoryManager()->findById($id);

            return $this->getCategoryManager()->getApiObject($entity, $locale);
        };

        $view = $this->responseGetById($id, $findCallback);

        return $this->handleView($view);
    }

    /**
     * Returns the sub-graph below the category which is assigned to the given parentId.
     * This method is used by the husky datagrid to load children of a category.
     * If request.flat is set, only the first level of the respective graph is returned in a flat format.
     *
     * @param Request $request
     * @param mixed $parentId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getChildrenAction($parentId, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);

        if ($request->get('flat') == 'true') {
            // check if parent exists
            $this->getCategoryManager()->findById($parentId);
            $list = $this->getListRepresentation($request, $locale, $parentId);
        } else {
            $entities = $this->getCategoryManager()->findChildrenByParentId($parentId);
            $categories = $this->getCategoryManager()->getApiObjects($entities, $locale);
            $list = new CollectionRepresentation($categories, self::$entityKey);
        }

        return $this->handleView($this->view($list, 200));
    }

    /**
     * Returns the whole category graph.
     * If request.rootKey is set, only the sub-graph below the category which is assigned to the given key is returned.
     * If request.flat is set, only the first level of the respective graph is returned in a flat format.
     * If request.expand is set, the paths to the respective categories are expanded.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $rootKey = $request->get('rootKey');

        if ($request->get('flat') == 'true') {
            $rootId = ($rootKey) ? $this->getCategoryManager()->findByKey($rootKey)->getId() : null;
            $expandIds = array_filter(explode(',', $request->get('expandIds')));
            $list = $this->getListRepresentation($request, $locale, $rootId, $expandIds);
        } else {
            $entities = $this->getCategoryManager()->findChildrenByParentKey($rootKey);
            $categories = $this->getCategoryManager()->getApiObjects($entities, $locale);
            $list = new CollectionRepresentation($categories, self::$entityKey);
        }

        return $this->handleView($this->view($list, 200));
    }

    /**
     * Adds a new category.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        return $this->saveCategory($request);
    }

    /**
     * Updates the category which is assigned to the given id.
     * Properties which are not set in the request will be removed from the category.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($id, Request $request)
    {
        return $this->saveCategory($request, $id);
    }

    /**
     * Partly updates the category which is assigned to the given id.
     * Properties which are not set in the request will not be changed.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction(Request $request, $id)
    {
        return $this->saveCategory($request, $id, true);
    }

    /**
     * Deletes the category which is assigned to the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $deleteCallback = function ($id) {
            $this->getCategoryManager()->delete($id);
        };

        $view = $this->responseDelete($id, $deleteCallback);

        return $this->handleView($view);
    }

    /**
     * Creates or updates a category based on the request.
     * If id is set, the category which is assigned to the given id is overwritten.
     * If patch is set, the category which is assigned to the given id is updated partially.
     *
     * @param Request $request
     * @param null $id
     * @param bool $patch
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws CategoryIdNotFoundException
     * @throws CategoryKeyNotUniqueException
     * @throws MissingArgumentException
     */
    protected function saveCategory(Request $request, $id = null, $patch = false)
    {
        $mediasData = $request->get('medias', []);
        $medias = null;
        if (array_key_exists('ids', $mediasData)) {
            $medias = $mediasData['ids'];
        }

        $locale = $this->getRequestParameter($request, 'locale', true);
        $data = [
            'id' => $id,
            'name' => (empty($request->get('name'))) ? null : $request->get('name'),
            'description' => (empty($request->get('description'))) ? null : $request->get('description'),
            'medias' => $medias,
            'key' => (empty($request->get('key'))) ? null : $request->get('key'),
            'meta' => $request->get('meta'),
            'parent' => $request->get('parent'),
        ];
        $entity = $this->getCategoryManager()->save($data, null, $locale, $patch);
        $category = $this->getCategoryManager()->getApiObject($entity, $locale);

        return $this->handleView($this->view($category, 200));
    }

    /**
     * Returns a category-list-representation for the category graph respective to the request.
     * The category-list-representation contains only the root level of the category graph.
     *
     * If parentId is set, the root level of the sub-graph below the category with the given parentId is returned.
     * If expandIds is set, the paths to the categories which are assigned to the ids are expanded.
     *
     * @param Request $request
     * @param $locale
     * @param null $parentId
     * @param array $expandIds
     *
     * @return CategoryListRepresentation
     */
    protected function getListRepresentation(Request $request, $locale, $parentId = null, $expandIds = [])
    {
        $listBuilder = $this->initializeListBuilder($locale);

        // disable pagination to simplify tree handling
        $listBuilder->limit(null);

        // collect categories which children should get loaded
        $parentIdsToExpand = [$parentId];
        if ($expandIds) {
            $pathIds = $this->get('sulu.repository.category')->findCategoryIdsBetween([$parentId], $expandIds);
            $parentIdsToExpand = array_merge($parentIdsToExpand, $pathIds);
        }

        // generate expressions for collected parent-categories
        $parentExpressions = [];
        foreach ($parentIdsToExpand as $parentId) {
            $parentExpressions[] = $listBuilder->createWhereExpression(
                $this->getCategoryManager()->getFieldDescriptor($locale, 'parent'),
                $parentId,
                ListBuilderInterface::WHERE_COMPARATOR_EQUAL
            );
        }

        // expand collected parents if search is not set, else search all categories
        if (!$request->get('search')) {
            if (count($parentExpressions) >= 2) {
                $listBuilder->addExpression($listBuilder->createOrExpression($parentExpressions));
            } elseif (count($parentExpressions) >= 1) {
                $listBuilder->addExpression($parentExpressions[0]);
            }
        }

        $results = $listBuilder->execute();
        foreach ($results as &$result) {
            $result['hasChildren'] = ($result['lft'] + 1) !== $result['rgt'];
        }

        return new CategoryListRepresentation(
            $results,
            self::$entityKey,
            'get_categories',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );
    }

    /**
     * Initializes and returns a ListBuilder instance which is used when returning a CategoryListRepresentation
     * for the given locale. The returned ListBuilder is initialized with the request-parameters and respective
     * select fields.
     *
     * @param $locale
     *
     * @return DoctrineListBuilder
     */
    private function initializeListBuilder($locale)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');

        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');

        $fieldDescriptors = $this->getCategoryManager()->getFieldDescriptors($locale);

        $listBuilder = $factory->create(self::$entityName);
        // sort by depth before initializing listbuilder with request parameter to avoid wrong sorting in frontend
        $listBuilder->sort($fieldDescriptors['depth']);
        $restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listBuilder->addSelectField($fieldDescriptors['depth']);
        $listBuilder->addSelectField($fieldDescriptors['parent']);
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['defaultLocale']);
        $listBuilder->addSelectField($fieldDescriptors['lft']);
        $listBuilder->addSelectField($fieldDescriptors['rgt']);

        return $listBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.settings.categories';
    }

    /**
     * Returns the CategoryManager.
     *
     * @return \Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface
     */
    private function getCategoryManager()
    {
        return $this->get('sulu_category.category_manager');
    }
}
