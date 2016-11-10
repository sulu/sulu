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

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\CategoryBundle\Category\KeywordManager;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Exception\KeywordIsMultipleReferencedException;
use Sulu\Bundle\CategoryBundle\Exception\KeywordNotUniqueException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides keywords for categories.
 */
class KeywordController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    const FORCE_OVERWRITE = 'overwrite';
    const FORCE_DETACH = 'detach';
    const FORCE_MERGE = 'merge';

    /**
     * {@inheritdoc}
     */
    protected static $entityKey = 'keywords';

    /**
     * Returns field-descriptors for keywords.
     *
     * @param int $categoryId
     *
     * @return Response
     *
     * @Get("/categories/{categoryId}/keywords/fields")
     */
    public function fieldsAction($categoryId)
    {
        return $this->handleView($this->view(array_values($this->getFieldDescriptors())));
    }

    /**
     * Returns list of keywords filtered by the category.
     *
     * @param int $categoryId
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction($categoryId, Request $request)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');

        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');

        /** @var CategoryInterface $category */
        $category = $this->getCategoryRepository()->find($categoryId);

        $fieldDescriptor = $this->getFieldDescriptors();

        $listBuilder = $factory->create($this->getParameter('sulu.model.keyword.class'));
        $restHelper->initializeListBuilder($listBuilder, $fieldDescriptor);

        $listBuilder->where($fieldDescriptor['locale'], $request->get('locale'));
        $listBuilder->where(
            $fieldDescriptor['categoryTranslationIds'],
            $category->findTranslationByLocale($request->get('locale'))
        );

        // should eliminate duplicates
        $listBuilder->distinct(true);
        $listBuilder->addGroupBy($fieldDescriptor['id']);

        $listResponse = $listBuilder->execute();

        $list = new ListRepresentation(
            $listResponse,
            self::$entityKey,
            'get_category_keywords',
            array_merge(['categoryId' => $categoryId], $request->query->all()),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );

        return $this->handleView($this->view($list, 200));
    }

    /**
     * Creates new keyword for given category.
     *
     * @param int $categoryId
     * @param Request $request
     *
     * @return Response
     */
    public function postAction($categoryId, Request $request)
    {
        /** @var KeywordInterface $keyword */
        $keyword = $this->getKeywordRepository()->createNew();
        $category = $this->getCategoryRepository()->findCategoryById($categoryId);
        $keyword->setKeyword($request->get('keyword'));
        $keyword->setLocale($request->get('locale'));

        $keyword = $this->getKeywordManager()->save($keyword, $category);

        $this->getEntityManager()->persist($keyword);
        $this->getEntityManager()->flush();

        return $this->handleView($this->view($keyword));
    }

    /**
     * Updates given keyword for given category.
     *
     * @param int $categoryId
     * @param int $keywordId
     * @param Request $request
     *
     * @return Response
     *
     * @throws KeywordIsMultipleReferencedException
     * @throws KeywordNotUniqueException
     */
    public function putAction($categoryId, $keywordId, Request $request)
    {
        $keyword = $this->getKeywordRepository()->findById($keywordId);

        if (!$keyword) {
            return $this->handleView($this->view(null, 404));
        }

        $force = $request->get('force');
        $category = $this->getCategoryRepository()->findCategoryById($categoryId);
        $keyword->setKeyword($request->get('keyword'));

        $keyword = $this->getKeywordManager()->save($keyword, $category, $force);

        $this->getEntityManager()->persist($keyword);
        $this->getEntityManager()->flush();

        return $this->handleView($this->view($keyword));
    }

    /**
     * Delete given keyword from given category.
     *
     * @param int $categoryId
     * @param int $keywordId
     *
     * @return Response
     */
    public function deleteAction($categoryId, $keywordId)
    {
        $keyword = $this->getKeywordRepository()->findById($keywordId);
        $category = $this->getCategoryRepository()->findCategoryById($categoryId);
        $this->getKeywordManager()->delete($keyword, $category);

        $this->getEntityManager()->flush();

        return $this->handleView($this->view());
    }

    /**
     * Delete given keyword from given category.
     *
     * @param int $categoryId
     * @param Request $request
     *
     * @return Response
     */
    public function cdeleteAction($categoryId, Request $request)
    {
        $category = $this->getCategoryRepository()->findCategoryById($categoryId);

        $ids = array_filter(explode(',', $request->get('ids')));
        foreach ($ids as $id) {
            $keyword = $this->getKeywordRepository()->findById($id);
            $this->getKeywordManager()->delete($keyword, $category);
        }

        $this->getEntityManager()->flush();

        return $this->handleView($this->view());
    }

    /**
     * @return KeywordManager
     */
    private function getKeywordManager()
    {
        return $this->get('sulu_category.keyword_manager');
    }

    /**
     * @return KeywordRepositoryInterface
     */
    private function getKeywordRepository()
    {
        return $this->get('sulu.repository.keyword');
    }

    /**
     * @return CategoryRepositoryInterface
     */
    private function getCategoryRepository()
    {
        return $this->get('sulu.repository.category');
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->get('doctrine.orm.entity_manager');
    }

    /**
     * Returns field descriptor for keyword.
     *
     * @return FieldDescriptorInterface[]
     */
    public function getFieldDescriptors()
    {
        return $this->get('sulu_core.list_builder.field_descriptor_factory')->getFieldDescriptorForClass(
            $this->getParameter('sulu.model.keyword.class')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.settings.categories';
    }
}
