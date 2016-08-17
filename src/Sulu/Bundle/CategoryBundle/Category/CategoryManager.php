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
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CategoryBundle\Event\CategoryDeleteEvent;
use Sulu\Bundle\CategoryBundle\Event\CategoryEvents;
use Sulu\Bundle\CategoryBundle\Exception\CategoryIdNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotUniqueException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryNameMissingException;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCaseFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * {@inheritdoc}
 */
class CategoryManager implements CategoryManagerInterface
{
    public static $categoryEntityName = CategoryInterface::class;
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
     * {@inheritdoc}
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
            $this->fieldDescriptors['lft'] = new DoctrineFieldDescriptor(
                'lft',
                'lft',
                self::$categoryEntityName,
                'public.lft',
                [],
                true
            );
            $this->fieldDescriptors['rgt'] = new DoctrineFieldDescriptor(
                'rgt',
                'rgt',
                self::$categoryEntityName,
                'public.rgt',
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
                'public.name',
                false,
                false,
                '',
                '',
                '',
                false
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
        }

        return $this->fieldDescriptors;
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id, $locale)
    {
        if (!$entity = $this->categoryRepository->findCategoryById($id)) {
            throw new CategoryIdNotFoundException($id);
        }

        return $this->getApiObject($entity, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function findByKey($key, $locale)
    {
        if (!$entity = $this->categoryRepository->findCategoryByKey($key)) {
            throw new CategoryKeyNotFoundException($key);
        }

        return $this->getApiObject($entity, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function findByIds(array $ids, $locale)
    {
        $entities = $this->categoryRepository->findCategoriesByIds($ids);

        return $this->getApiObjects($entities, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function findChildrenByParentId($locale, $parentId = null)
    {
        if ($parentId && !$this->categoryRepository->isCategoryId($parentId)) {
            throw new CategoryIdNotFoundException($parentId);
        }

        $entities = $this->categoryRepository->findChildrenCategoriesByParentId($parentId);

        return $this->getApiObjects($entities, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function findChildrenByParentKey($locale, $parentKey = null)
    {
        if ($parentKey && !$this->categoryRepository->isCategoryKey($parentKey)) {
            throw new CategoryKeyNotFoundException($parentKey);
        }

        $entities = $this->categoryRepository->findChildrenCategoriesByParentKey($parentKey);

        return $this->getApiObjects($entities, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $locale, $patch = false)
    {
        if ($this->getProperty($data, 'id')) {
            $categoryEntity = $this->findById($this->getProperty($data, 'id'), $locale)->getEntity();
        } else {
            $categoryEntity = $this->categoryRepository->createNew();
        }

        $categoryWrapper = $this->getApiObject($categoryEntity, $locale);

        if (!$patch || $this->getProperty($data, 'name')) {
            $categoryWrapper->setName($this->getProperty($data, 'name', null));
        }
        $key = $this->getProperty($data, 'key');
        if (!$patch || $key) {
            $categoryWrapper->setKey($key);
        }
        if (!$patch || $this->getProperty($data, 'meta')) {
            $categoryWrapper->setMeta($this->getProperty($data, 'meta', []));
        }
        if (!$patch || $this->getProperty($data, 'parent')) {
            if ($this->getProperty($data, 'parent')) {
                $parentEntity = $this->findById($this->getProperty($data, 'parent'), $locale)->getEntity();
            } else {
                $parentEntity = null;
            }
            $categoryWrapper->setParent($parentEntity);
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

        return $this->getApiObject($categoryEntity, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        if (!$entity = $this->categoryRepository->findCategoryById($id)) {
            throw new CategoryIdNotFoundException($id);
        }

        /** @var CategoryTranslation $translation */
        foreach ($entity->getTranslations() as $translation) {
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
     * Returns an API-Object for a given category-entity. The API-Object wraps the entity
     * and provides neat getters and setters. If the given object is already an API-object,
     * the respective entity is used for wrapping.
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
        } elseif (!$category instanceof CategoryInterface) {
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
            function($entity) use ($locale) {
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
        return (array_key_exists($key, $data)) ? $data[$key] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function find($parent = null, $depth = null)
    {
        if ($parent && !$this->categoryRepository->isCategoryId($parent)) {
            throw new CategoryIdNotFoundException($parent);
        }

        return $this->categoryRepository->findCategories($parent, $depth);
    }

    /**
     * {@inheritdoc}
     */
    public function findChildren($key)
    {
        $this->findChildrenByParentKey($key);
    }
}
