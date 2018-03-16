<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Api\Category as CategoryWrapper;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMetaRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Event\CategoryDeleteEvent;
use Sulu\Bundle\CategoryBundle\Event\CategoryEvents;
use Sulu\Bundle\CategoryBundle\Exception\CategoryIdNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotUniqueException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryNameMissingException;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCaseFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * {@inheritdoc}
 */
class CategoryManager implements CategoryManagerInterface
{
    public static $categoryEntityName = CategoryInterface::class;

    public static $catTranslationEntityName = CategoryTranslationInterface::class;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var CategoryMetaRepositoryInterface
     */
    private $categoryMetaRepository;

    /**
     * @var CategoryTranslationRepositoryInterface
     */
    private $categoryTranslationRepository;

    /**
     * @var KeywordManagerInterface
     */
    private $keywordManager;

    /**
     * @var DoctrineFieldDescriptor[]
     */
    private $fieldDescriptors;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryMetaRepositoryInterface $categoryMetaRepository,
        CategoryTranslationRepositoryInterface $categoryTranslationRepository,
        UserRepositoryInterface $userRepository,
        KeywordManagerInterface $keywordManager,
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->categoryMetaRepository = $categoryMetaRepository;
        $this->categoryTranslationRepository = $categoryTranslationRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->keywordManager = $keywordManager;
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id)
    {
        if (!$entity = $this->categoryRepository->findCategoryById($id)) {
            throw new CategoryIdNotFoundException($id);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function findByKey($key)
    {
        if (!$entity = $this->categoryRepository->findCategoryByKey($key)) {
            throw new CategoryKeyNotFoundException($key);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function findByIds(array $ids)
    {
        return $this->categoryRepository->findCategoriesByIds($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function findChildrenByParentId($parentId = null)
    {
        if ($parentId && !$this->categoryRepository->isCategoryId($parentId)) {
            throw new CategoryIdNotFoundException($parentId);
        }

        return $this->categoryRepository->findChildrenCategoriesByParentId($parentId);
    }

    /**
     * {@inheritdoc}
     */
    public function findChildrenByParentKey($parentKey = null)
    {
        if ($parentKey && !$this->categoryRepository->isCategoryKey($parentKey)) {
            throw new CategoryKeyNotFoundException($parentKey);
        }

        return $this->categoryRepository->findChildrenCategoriesByParentKey($parentKey);
    }

    /**
     * Returns category-translation or create a new one.
     *
     * @param CategoryInterface $category
     * @param CategoryWrapper $categoryWrapper
     * @param string $locale
     *
     * @return CategoryTranslationInterface
     */
    private function findOrCreateCategoryTranslation(
        CategoryInterface $category,
        CategoryWrapper $categoryWrapper,
        $locale
    ) {
        $translationEntity = $category->findTranslationByLocale($locale);
        if (!$translationEntity) {
            $translationEntity = $this->categoryTranslationRepository->createNew();
            $translationEntity->setLocale($locale);
            $categoryWrapper->setTranslation($translationEntity);
        }

        return $translationEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $userId, $locale, $patch = false)
    {
        if ($this->getProperty($data, 'id')) {
            $categoryEntity = $this->findById($this->getProperty($data, 'id'));
        } else {
            $categoryEntity = $this->categoryRepository->createNew();
        }

        // set user properties if userId is set. else these properties are set by the UserBlameSubscriber on save.
        if ($user = ($userId) ? $this->userRepository->findUserById($userId) : null) {
            if (!$categoryEntity->getCreator()) {
                $categoryEntity->setCreator($user);
            }
            $categoryEntity->setChanger($user);
        }

        $categoryWrapper = $this->getApiObject($categoryEntity, $locale);

        if (!$patch || $this->getProperty($data, 'name')) {
            $translationEntity = $this->findOrCreateCategoryTranslation($categoryEntity, $categoryWrapper, $locale);
            $translationEntity->setTranslation($this->getProperty($data, 'name', null));
        }

        if (!$patch || $this->getProperty($data, 'description')) {
            $translationEntity = $this->findOrCreateCategoryTranslation($categoryEntity, $categoryWrapper, $locale);
            $translationEntity->setDescription($this->getProperty($data, 'description', null));
        }

        if (!$patch || $this->getProperty($data, 'medias')) {
            $translationEntity = $this->findOrCreateCategoryTranslation($categoryEntity, $categoryWrapper, $locale);
            $translationEntity->setMedias(
                array_map(
                    function ($item) {
                        return $this->em->getReference(Media::class, $item);
                    },
                    $this->getProperty($data, 'medias', [])
                )
            );
        }

        $key = $this->getProperty($data, 'key');
        if (!$patch || $key) {
            $categoryWrapper->setKey($key);
        }
        if (!$patch || $this->getProperty($data, 'meta')) {
            $metaData = (is_array($this->getProperty($data, 'meta'))) ? $this->getProperty($data, 'meta') : [];

            $metaEntities = [];
            foreach ($metaData as $meta) {
                $metaEntity = $this->categoryMetaRepository->createNew();
                $metaEntity->setId($this->getProperty($meta, 'id'));
                $metaEntity->setKey($this->getProperty($meta, 'key'));
                $metaEntity->setValue($this->getProperty($meta, 'value'));
                $metaEntity->setLocale($this->getProperty($meta, 'locale'));
                $metaEntities[] = $metaEntity;
            }
            $categoryWrapper->setMeta($metaEntities);
        }
        if (!$patch || $this->getProperty($data, 'parent')) {
            $parentCategory = null;
            if ($this->getProperty($data, 'parent')) {
                $parentCategory = $this->findById($this->getProperty($data, 'parent'));
            }
            $categoryWrapper->setParent($parentCategory);
        }

        if (!$categoryWrapper->getName()) {
            throw new CategoryNameMissingException();
        }

        $categoryEntity = $categoryWrapper->getEntity();
        $this->em->persist($categoryEntity);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new CategoryKeyNotUniqueException($key);
        }

        return $categoryEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        if (!$entity = $this->categoryRepository->findCategoryById($id)) {
            throw new CategoryIdNotFoundException($id);
        }

        foreach ($entity->getTranslations() as $translation) {
            /** @var CategoryTranslationInterface $translation */
            foreach ($translation->getKeywords() as $keyword) {
                $this->keywordManager->delete($keyword, $entity);
            }
        }

        $this->em->remove($entity);
        $this->em->flush();

        // throw a category.delete event
        $event = new CategoryDeleteEvent($entity);
        $this->eventDispatcher->dispatch(CategoryEvents::CATEGORY_DELETE, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function move($id, $parent)
    {
        if (!$category = $this->categoryRepository->findCategoryById($id)) {
            throw new CategoryIdNotFoundException($id);
        }

        $parentCategory = null;
        if ($parent && !$parentCategory = $this->categoryRepository->findCategoryById($parent)) {
            throw new CategoryIdNotFoundException($parent);
        }

        $category->setParent($parentCategory);
        $this->em->flush();

        return $category;
    }

    /**
     * Returns an API-Object for a given category-entity. The API-Object wraps the entity
     * and provides neat getters and setters. If the given object is already an API-object,
     * the associated entity is used for wrapping.
     *
     * @param $category
     * @param string $locale
     *
     * @return null|CategoryWrapper
     */
    public function getApiObject($category, $locale)
    {
        if ($category instanceof CategoryWrapper) {
            $category = $category->getEntity();
        }
        if (!$category instanceof CategoryInterface) {
            return;
        }

        return new CategoryWrapper($category, $locale);
    }

    /**
     * Returns an array of API-Objects for a given array of category-entities.
     * The returned array can contain null-values, if the given entities are not valid.
     *
     * @param $entities
     * @param $locale
     *
     * @return array
     */
    public function getApiObjects($entities, $locale)
    {
        return array_map(
            function ($entity) use ($locale) {
                return $this->getApiObject($entity, $locale);
            },
            $entities
        );
    }

    /**
     * Return the value of a key in a given data-array.
     * If the given key does not exist, the given default value is returned.
     *
     * @param array $data
     * @param string $key
     * @param string $default
     *
     * @return string|null
     */
    private function getProperty($data, $key, $default = null)
    {
        return (array_key_exists($key, $data) && null !== $data[$key]) ? $data[$key] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function find($parent = null, $depth = null, $sortBy = null, $sortOrder = null)
    {
        @trigger_error(
            __METHOD__ . '() is deprecated since version 1.4 and will be removed in 2.0. Use findChildrenByParentId() instead.',
            E_USER_DEPRECATED
        );

        if ($parent && !$this->categoryRepository->isCategoryId($parent)) {
            throw new CategoryIdNotFoundException($parent);
        }

        return $this->categoryRepository->findCategories($parent, $depth, $sortBy, $sortOrder);
    }

    /**
     * {@inheritdoc}
     */
    public function findChildren($key, $sortBy = null, $sortOrder = null)
    {
        @trigger_error(
            __METHOD__ . '() is deprecated since version 1.4 and will be removed in 2.0. Use findChildrenByParentKey() instead.',
            E_USER_DEPRECATED
        );

        return $this->categoryRepository->findChildren($key, $sortBy, $sortOrder);
    }
}
