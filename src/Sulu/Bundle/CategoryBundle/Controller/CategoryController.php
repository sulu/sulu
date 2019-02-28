<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\CategoryBundle\Api\RootCategory;
use Sulu\Bundle\CategoryBundle\Category\CategoryListRepresentation;
use Sulu\Component\Rest\Exception\RestException;
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

    protected static $entityKey = 'categories';

    public function getAction($id, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $findCallback = function($id) use ($locale) {
            $entity = $this->getCategoryManager()->findById($id);

            return $this->getCategoryManager()->getApiObject($entity, $locale);
        };

        $view = $this->responseGetById($id, $findCallback);

        return $this->handleView($view);
    }

    public function cgetAction(Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $rootKey = $request->get('rootKey');
        $parentId = $request->get('parentId');
        $includeRoot = $this->getBooleanRequestParameter($request, 'includeRoot', false, false);

        if ('root' === $parentId) {
            $includeRoot = false;
            $parentId = null;
        }

        if ('true' == $request->get('flat')) {
            $rootId = ($rootKey) ? $this->getCategoryManager()->findByKey($rootKey)->getId() : null;
            $expandedIds = array_filter(explode(',', $request->get('expandedIds', $request->get('selectedIds'))));
            $list = $this->getListRepresentation(
                $request,
                $locale,
                $parentId ?? $rootId,
                $expandedIds,
                $request->query->has('expandedIds'),
                $includeRoot
            );
        } elseif ($request->query->has('ids')) {
            $entities = $this->getCategoryManager()->findByIds(explode(',', $request->query->get('ids')));
            $categories = $this->getCategoryManager()->getApiObjects($entities, $locale);
            $list = new CollectionRepresentation($categories, self::$entityKey);
        } else {
            $entities = $this->getCategoryManager()->findChildrenByParentKey($rootKey);
            $categories = $this->getCategoryManager()->getApiObjects($entities, $locale);
            $list = new CollectionRepresentation($categories, self::$entityKey);
        }

        return $this->handleView($this->view($list, 200));
    }

    /**
     * @Post("categories/{id}")
     */
    public function postTriggerAction($id, Request $request)
    {
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            switch ($action) {
                case 'move':
                    return $this->move($id, $request);
                    break;
                default:
                    throw new RestException(sprintf('Unrecognized action: "%s"', $action));
            }
        } catch (RestException $ex) {
            $view = $this->view($ex->toArray(), 400);

            return $this->handleView($view);
        }
    }

    private function move($id, Request $request)
    {
        $destination = $this->getRequestParameter($request, 'destination', true);
        if ('root' === $destination) {
            $destination = null;
        }

        $categoryManager = $this->getCategoryManager();
        $category = $categoryManager->move($id, $destination);

        return $this->handleView($this->view($categoryManager->getApiObject($category, $request->get('locale'))));
    }

    public function postAction(Request $request)
    {
        return $this->saveCategory($request);
    }

    public function putAction($id, Request $request)
    {
        return $this->saveCategory($request, $id);
    }

    public function patchAction(Request $request, $id)
    {
        return $this->saveCategory($request, $id, true);
    }

    public function deleteAction($id)
    {
        $deleteCallback = function($id) {
            $this->getCategoryManager()->delete($id);
        };

        $view = $this->responseDelete($id, $deleteCallback);

        return $this->handleView($view);
    }

    protected function saveCategory(Request $request, $id = null, $patch = false)
    {
        $mediasData = $request->get('medias');
        $medias = null;
        if ($mediasData && array_key_exists('ids', $mediasData)) {
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
            'parent' => $request->get('parentId'),
        ];
        $entity = $this->getCategoryManager()->save($data, null, $locale, $patch);
        $category = $this->getCategoryManager()->getApiObject($entity, $locale);

        return $this->handleView($this->view($category, 200));
    }

    protected function getListRepresentation(
        Request $request,
        $locale,
        $parentId = null,
        $expandedIds = [],
        $expandSelf = false,
        $includeRoot = false
    ) {
        $listBuilder = $this->initializeListBuilder($locale);

        // disable pagination to simplify tree handling
        $listBuilder->limit(null);

        // collect categories which children should get loaded
        $idsToExpand = [$parentId];
        if ($expandedIds) {
            $pathIds = $this->get('sulu.repository.category')->findCategoryIdsBetween([$parentId], $expandedIds);
            $idsToExpand = array_merge($idsToExpand, $pathIds);
            if ($expandSelf) {
                $idsToExpand = array_merge($idsToExpand, $expandedIds);
            }
        }

        if ('csv' === $request->getRequestFormat()) {
            $idsToExpand = array_filter($idsToExpand);
        }

        // generate expressions for collected parent-categories
        $parentExpressions = [];
        foreach ($idsToExpand as $idToExpand) {
            $parentExpressions[] = $listBuilder->createWhereExpression(
                $listBuilder->getFieldDescriptor('parent'),
                $idToExpand,
                ListBuilderInterface::WHERE_COMPARATOR_EQUAL
            );
        }

        if (!$request->get('search')) {
            // expand collected parents if search is not set
            if (count($parentExpressions) >= 2) {
                $listBuilder->addExpression($listBuilder->createOrExpression($parentExpressions));
            } elseif (count($parentExpressions) >= 1) {
                $listBuilder->addExpression($parentExpressions[0]);
            }
        } elseif ($request->get('search') && $parentId && !$expandedIds) {
            // filter for parentId when search is active and no expandedIds are set
            $listBuilder->addExpression($parentExpressions[0]);
        }

        $categories = $listBuilder->execute();

        foreach ($categories as &$category) {
            $category['hasChildren'] = ($category['lft'] + 1) !== $category['rgt'];
        }

        if (!empty($expandedIds)) {
            $categoriesByParentId = [];
            foreach ($categories as &$category) {
                $categoryParentId = $category['parent'];
                if (!isset($categoriesByParentId[$categoryParentId])) {
                    $categoriesByParentId[$categoryParentId] = [];
                }
                $categoriesByParentId[$categoryParentId][] = &$category;
            }

            foreach ($categories as &$category) {
                if (!isset($categoriesByParentId[$category['id']])) {
                    continue;
                }

                $category['_embedded'] = [
                    self::$entityKey => $categoriesByParentId[$category['id']],
                ];
            }

            $categories = $categoriesByParentId[$parentId];
        }

        if ($includeRoot && !$parentId) {
            $categories = [
                new RootCategory(
                    $this->get('translator')->trans('sulu_category.all_categories', [], 'admin'),
                    $categories
                ),
            ];
        }

        return new CategoryListRepresentation(
            $categories,
            self::$entityKey,
            'get_categories',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );
    }

    private function initializeListBuilder($locale)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');

        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');

        $fieldDescriptors = $this->getFieldDescriptors();

        $listBuilder = $factory->create($this->getParameter('sulu.model.category.class'));
        $listBuilder->setParameter('locale', $locale);
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

    public function getSecurityContext()
    {
        return 'sulu.settings.categories';
    }

    private function getCategoryManager()
    {
        return $this->get('sulu_category.category_manager');
    }

    private function getFieldDescriptors()
    {
        return $this->get('sulu_core.list_builder.field_descriptor_factory')
            ->getFieldDescriptors('categories');
    }
}
