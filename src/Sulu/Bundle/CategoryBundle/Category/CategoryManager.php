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
    public static $catTranslationEntityName = CategoryTranslationInterface::class;

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
        ObjectManager $em,
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

        $key = $this->getProperty($data, 'key');
        if (!$patch || $key) {
            $categoryEntity->setKey($key);
        }

        if (!$patch || $this->getProperty($data, 'parent')) {
            $parentId = $this->getProperty($data, 'parent');
            $parentEntity = ($parentId) ? $this->findById($parentId) : null;
            $categoryEntity->setParent($parentEntity);
        }

        if (!$patch || $this->getProperty($data, 'name')) {
            $this->setCategoryName($categoryEntity, $this->getProperty($data, 'name'), $locale);
        }

        if (!$patch || $this->getProperty($data, 'meta')) {
            $metaArray = (is_array($this->getProperty($data, 'meta'))) ? $this->getProperty($data, 'meta') : [];
            $this->setCategoryMeta($categoryEntity, $metaArray);
        }

        if (!$categoryEntity->findTranslationByLocale($locale)
            || !$categoryEntity->findTranslationByLocale($locale)->getTranslation()) {
            throw new CategoryNameMissingException();
        }

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

        /** @var CategoryTranslationInterface $translation */
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
     * Sets the name of the given locale of the given category-entity.
     * If the given entity is not translated in the given locale currently, a new category-translation is created
     * and added to the entity.
     * If the given entity is not translated in any locale currently, a new category-translation is created and
     * added as default tranlsation to the entity.
     *
     * @param $entity
     * @param $name
     * @param $locale
     */
    private function setCategoryName($entity, $name, $locale)
    {
        $translationEntity = $entity->findTranslationByLocale($locale);
        if (!$translationEntity) {
            $translationEntity = $this->categoryTranslationRepository->createNew();
            $entity->addTranslation($translationEntity);
        }

        $translationEntity->setCategory($entity);
        $translationEntity->setLocale($locale);
        $translationEntity->setTranslation($name);

        if ($entity->getId() === null && $entity->getDefaultLocale() === null) {
            $entity->setDefaultLocale($translationEntity->getLocale());
        }
    }

    /**
     * Updates the meta of the given category-entity to the data in the given meta-array.
     * Meta which is already set to the entity is updated.
     * Meta which is currently not set to the entity is added.
     *
     * Despite the method name, no meta is removed from the entity to ensure backward compatibility.
     *
     * @param $entity
     * @param $metaArray
     */
    private function setCategoryMeta($entity, $metaArray)
    {
        foreach ($metaArray as $meta) {
            $metaEntity = $entity->findMetaById($this->getProperty($meta, 'id'));
            if (!$metaEntity) {
                $metaEntity = $this->categoryMetaRepository->createNew();
                $entity->addMeta($metaEntity);
            }

            $metaEntity->setCategory($entity);
            $metaEntity->setKey($this->getProperty($meta, 'key'));
            $metaEntity->setValue($this->getProperty($meta, 'value'));
            $metaEntity->setLocale($this->getProperty($meta, 'locale'));
        }
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
    public function find($parent = null, $depth = null, $sortBy = null, $sortOrder = null)
    {
        @trigger_error(__method__ . '() is deprecated since version 1.4 and will be removed in 2.0. Use findChildrenByParentId() instead.', E_USER_DEPRECATED);

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
        @trigger_error(__method__ . '() is deprecated since version 1.4 and will be removed in 2.0. Use findChildrenByParentKey() instead.', E_USER_DEPRECATED);

        return $this->categoryRepository->findChildren($key, $sortBy, $sortOrder);
    }
}
