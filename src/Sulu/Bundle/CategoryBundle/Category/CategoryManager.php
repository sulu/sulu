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

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sulu\Bundle\CategoryBundle\Api\Category as CategoryWrapper;
use Sulu\Bundle\CategoryBundle\Category\Exception\KeyNotUniqueException;
use Sulu\Bundle\CategoryBundle\Entity\Category as CategoryEntity;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CategoryBundle\Event\CategoryDeleteEvent;
use Sulu\Bundle\CategoryBundle\Event\CategoryEvents;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCaseFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Responsible for centralized Category Management.
 */
class CategoryManager implements CategoryManagerInterface
{
    public static $categoryEntityName = 'SuluCategoryBundle:Category';

    public static $catTranslationEntityName = 'SuluCategoryBundle:CategoryTranslation';

    /**
     * @var ObjectManager
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
     * @var KeywordManagerInterface
     */
    private $keywordManager;

    /**
     * @var DoctrineFieldDescriptor[]
     */
    private $fieldDescriptors;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        UserRepositoryInterface $userRepository,
        KeywordManagerInterface $keywordManager,
        ObjectManager $em,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->keywordManager = $keywordManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDescriptor($locale, $key)
    {
        return $this->getFieldDescriptors($locale)[$key];
    }

    /**
     * Initializes the field descriptors used by the list-helper.
     */
    public function getFieldDescriptors($locale)
    {
        if (null === $this->fieldDescriptors) {
            $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
                'id',
                'id',
                self::$categoryEntityName,
                'public.id',
                [],
                true
            );
            $this->fieldDescriptors['key'] = new DoctrineFieldDescriptor(
                'key',
                'key',
                self::$categoryEntityName,
                'public.key',
                [],
                true
            );
            $this->fieldDescriptors['defaultLocale'] = new DoctrineFieldDescriptor(
                'defaultLocale',
                'defaultLocale',
                self::$categoryEntityName,
                'public.default',
                [],
                false
            );
            $this->fieldDescriptors['name'] = new DoctrineCaseFieldDescriptor(
                'name',
                new DoctrineDescriptor(
                    'translation',
                    'translation',
                    [
                        'translation' => new DoctrineJoinDescriptor(
                            'translation',
                            self::$categoryEntityName . '.translations',
                            sprintf('translation.locale = \'%s\'', $locale)
                        ),
                    ]
                ),
                new DoctrineDescriptor(
                    'defaultTranslation',
                    'translation',
                    [
                        'defaultTranslation' => new DoctrineJoinDescriptor(
                            'defaultTranslation',
                            self::$categoryEntityName . '.translations',
                            sprintf('defaultTranslation.locale = %s.defaultLocale', self::$categoryEntityName)
                        ),
                    ]
                ),
                'public.name'
            );
            $this->fieldDescriptors['locale'] = new DoctrineCaseFieldDescriptor(
                'locale',
                new DoctrineDescriptor(
                    'translation',
                    'locale',
                    [
                        'translation' => new DoctrineJoinDescriptor(
                            'translation',
                            self::$categoryEntityName . '.translations',
                            sprintf('translation.locale = \'%s\'', $locale)
                        ),
                    ]
                ),
                new DoctrineDescriptor(
                    'defaultTranslation',
                    'locale',
                    [
                        'defaultTranslation' => new DoctrineJoinDescriptor(
                            'defaultTranslation',
                            self::$categoryEntityName . '.translations',
                            sprintf('defaultTranslation.locale = %s.defaultLocale', self::$categoryEntityName)
                        ),
                    ]
                ),
                'public.locale'
            );
            $this->fieldDescriptors['created'] = new DoctrineFieldDescriptor(
                'created',
                'created',
                self::$categoryEntityName,
                'public.created',
                [],
                true
            );
            $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
                'changed',
                'changed',
                self::$categoryEntityName,
                'public.changed',
                [],
                true
            );
            $this->fieldDescriptors['depth'] = new DoctrineFieldDescriptor(
                'depth',
                'depth',
                self::$categoryEntityName,
                'public.depth',
                [],
                false
            );
            $this->fieldDescriptors['parent'] = new DoctrineFieldDescriptor(
                'id',
                'parent',
                self::$categoryEntityName . 'Parent',
                'category.parent',
                [
                    self::$categoryEntityName . 'Parent' => new DoctrineJoinDescriptor(
                            self::$categoryEntityName,
                            self::$categoryEntityName . '.parent'
                        ),
                ],
                false
            );
            $this->fieldDescriptors['hasChildren'] = new DoctrineFieldDescriptor(
                'id',
                'hasChildren',
                self::$categoryEntityName . 'Children',
                'category.children',
                [
                    self::$categoryEntityName . 'Children' => new DoctrineJoinDescriptor(
                            self::$categoryEntityName,
                            self::$categoryEntityName . '.children'
                        ),
                ],
                false
            );
        }

        return $this->fieldDescriptors;
    }

    /**
     * Returns categories with a given parent and/or a given depth-level
     * if no arguments passed returns all categories.
     *
     * @param int         $parent    the id of the parent to filter for
     * @param int         $depth     the depth-level to filter for
     * @param string|null $sortBy    column name to sort the categories by
     * @param string|null $sortOrder sort order
     *
     * @return CategoryEntity[]
     */
    public function find($parent = null, $depth = null, $sortBy = null, $sortOrder = null)
    {
        return $this->categoryRepository->findCategories($parent, $depth, $sortBy, $sortOrder);
    }

    /**
     * Returns the children for a given category.
     *
     * @param int         $key       the key of the category to search the children for
     * @param string|null $sortBy    column name to sort by
     * @param string|null $sortOrder sort order
     *
     * @return CategoryEntity[]
     */
    public function findChildren($key, $sortBy = null, $sortOrder = null)
    {
        return $this->categoryRepository->findChildren($key, $sortBy, $sortOrder);
    }

    /**
     * Returns a category with a given id.
     *
     * @param int $id the id of the category
     *
     * @return CategoryEntity
     */
    public function findById($id)
    {
        return $this->categoryRepository->findCategoryById($id);
    }

    /**
     * Returns a category with a given key.
     *
     * @param string $key the key of the category
     *
     * @return CategoryEntity
     */
    public function findByKey($key)
    {
        return $this->categoryRepository->findCategoryByKey($key);
    }

    /**
     * Returns the categories with the given ids.
     *
     * @param array $ids
     *
     * @return CategoryEntity[]
     */
    public function findByIds(array $ids)
    {
        return $this->categoryRepository->findCategoryByIds($ids);
    }

    /**
     * Creates a new category or overrides an existing one.
     *
     * @param array $data   The data of the category to save
     * @param int   $userId The id of the user, who is doing this change
     *
     * @throws KeyNotUniqueException
     *
     * @return CategoryEntity
     */
    public function save($data, $userId)
    {
        try {
            if (isset($data['id'])) {
                return $this->modifyCategory($data, $this->getUser($userId));
            } else {
                return $this->createCategory($data, $this->getUser($userId));
            }
        } catch (UniqueConstraintViolationException $e) {
            throw new KeyNotUniqueException($data['key'], $e);
        }
    }

    /**
     * Deletes a category with a given id.
     *
     * @param int $id the id of the category to delete
     *
     * @throws EntityNotFoundException
     */
    public function delete($id)
    {
        $categoryEntity = $this->categoryRepository->findCategoryById($id);

        if (!$categoryEntity) {
            throw new EntityNotFoundException('SuluCategoryBundle:Category', $id);
        }

        /** @var CategoryTranslation $translation */
        foreach ($categoryEntity->getTranslations() as $translation) {
            foreach ($translation->getKeywords() as $keyword) {
                $this->keywordManager->delete($keyword, $categoryEntity);
            }
        }

        $this->em->remove($categoryEntity);
        $this->em->flush();

        // throw a category.delete event
        $event = new CategoryDeleteEvent($categoryEntity);
        $this->eventDispatcher->dispatch(CategoryEvents::CATEGORY_DELETE, $event);
    }

    /**
     * Returns an API-Object for a given category-entity. The API-Object wraps the entity
     * and provides neat getters and setters.
     *
     * @param Category $category
     * @param string   $locale
     *
     * @return null|CategoryWrapper
     */
    public function getApiObject($category, $locale)
    {
        if ($category instanceof CategoryEntity) {
            return new CategoryWrapper($category, $locale);
        } else {
            return;
        }
    }

    /**
     * Same as getApiObject, but takes multiple category-entities.
     *
     * @param Category[] $categories
     * @param string     $locale
     *
     * @return CategoryWrapper[]
     */
    public function getApiObjects($categories, $locale)
    {
        if (empty($categories)) {
            return [];
        }

        $arrReturn = [];
        foreach ($categories as $category) {
            array_push($arrReturn, $this->getApiObject($category, $locale));
        }

        return $arrReturn;
    }

    /**
     * Returns a user for a given user-id.
     *
     * @param $userId
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    private function getUser($userId)
    {
        return $this->userRepository->findUserById($userId);
    }

    /**
     * Creates a new category with given data.
     *
     * @param $data
     * @param $user
     *
     * @return CategoryEntity
     */
    private function createCategory($data, $user)
    {
        $categoryEntity = new CategoryEntity();
        $categoryEntity->setCreator($user);
        $categoryEntity->setChanger($user);

        $categoryWrapper = $this->getApiObject($categoryEntity, $data['locale']);
        $categoryWrapper->setName($data['name']);
        if (array_key_exists('key', $data)) {
            $categoryWrapper->setKey($data['key']);
        }
        if (array_key_exists('meta', $data)) {
            $categoryWrapper->setMeta($data['meta']);
        }
        if (array_key_exists('parent', $data)) {
            $parentEntity = $this->findById($data['parent']);
            $categoryWrapper->setParent($parentEntity);
        }

        $categoryEntity = $categoryWrapper->getEntity();
        $this->em->persist($categoryEntity);
        $this->em->flush();

        return $categoryEntity;
    }

    /**
     * Modifies an existing category with given data.
     *
     * @param $data
     * @param $user
     *
     * @return CategoryEntity
     *
     * @throws EntityNotFoundException
     */
    private function modifyCategory($data, $user)
    {
        $categoryEntity = $this->findById($data['id']);
        if (!$categoryEntity) {
            throw new EntityNotFoundException($categoryEntity, $data['id']);
        }

        $categoryEntity->setChanger($user);

        $categoryWrapper = $this->getApiObject($categoryEntity, $data['locale']);
        // set key
        if (array_key_exists('key', $data)) {
            $categoryWrapper->setKey($data['key']);
        }
        // set name
        if (array_key_exists('name', $data)) {
            $categoryWrapper->setName($data['name']);
        }
        // set meta
        if (array_key_exists('meta', $data)) {
            $categoryWrapper->setMeta($data['meta']);
        }
        $categoryEntity = $categoryWrapper->getEntity();
        $this->em->persist($categoryEntity);
        $this->em->flush();

        return $categoryEntity;
    }
}
