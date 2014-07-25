<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Sulu\Bundle\CategoryBundle\Entity\Category as CategoryEntity;
use Sulu\Bundle\CategoryBundle\Api\Category as CategoryWrapper;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Bundle\CategoryBundle\Event\CategoryEvents;
use Sulu\Bundle\CategoryBundle\Event\CategoryDeleteEvent;
use Sulu\Component\Security\UserRepositoryInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Responsible for centralized Category Management
 * @package Sulu\Bundle\CategoryBundle\Category
 */
class CategoryManager implements CategoryManagerInterface
{
    protected static $categoryEntityName = 'SuluCategoryBundle:Category';
    protected static $categoryTranslationEntityName = 'SuluCategoryBundle:CategoryTranslation';

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
     * Describes the fields, which are handled by the controller
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors = array();

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        UserRepositoryInterface $userRepository,
        ObjectManager $em,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldDescriptors()
    {
        return $this->fieldDescriptors;
    }


    /**
     * {@inheritDoc}
     */
    public function getFieldDescriptor($key)
    {
        return $this->fieldDescriptors[$key];
    }

    /**
     * Initializes the field descriptors used by the list-helper
     */
    public function createFieldDescriptors()
    {
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$categoryEntityName,
            'public.id',
            array(),
            true
        );
        $this->fieldDescriptors['name'] = new DoctrineFieldDescriptor(
            'translation',
            'name',
            self::$categoryTranslationEntityName,
            'public.name',
            array(
                self::$categoryTranslationEntityName => new DoctrineJoinDescriptor(
                        self::$categoryTranslationEntityName,
                        self::$categoryEntityName .
                        '.translations'
                    )
            )
        );
        $this->fieldDescriptors['created'] = new DoctrineFieldDescriptor(
            'created',
            'created',
            self::$categoryEntityName,
            'public.created',
            array(),
            true
        );
        $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
            'changed',
            'changed',
            self::$categoryEntityName,
            'public.changed',
            array(),
            true
        );

    }

    /**
     * Returns tags with a given parent and/or a given depth-level
     * if no arguments passed returns all categories
     * @param array $ids array of white-list of ids to filter
     * @param int $parent the id of the parent to filter for
     * @param int $depth the depth-level to filter for
     * @param string|null $sortBy column name to sort the categories by
     * @param string|null $sortOrder sort order
     * @return CategoryEntity[]
     */
    public function find($ids = null, $parent = null, $depth = null, $sortBy = null, $sortOrder = null)
    {
        return $this->categoryRepository->findCategories($ids, $parent, $depth, $sortBy, $sortOrder);
    }

    /**
     * Returns a category with a given id
     * @param int $id the id of the category
     * @return CategoryEntity
     */
    public function findById($id)
    {
        return $this->categoryRepository->findCategoryById($id);
    }

    /**
     * Creates a new category or overrides an existing one
     * @param array $data The data of the category to save
     * @param int $userId The id of the user, who is doing this change
     * @return CategoryEntity
     */
    public function save($data, $userId)
    {
        if (isset($data['id'])) {
            return $this->modifyCategory($data, $this->getUser($userId));
        } else {
            return $this->createCategory($data, $this->getUser($userId));
        }
    }

    /**
     * Deletes a category with a given id
     * @param int $id the id of the category to delete
     * @throws EntityNotFoundException
     */
    public function delete($id)
    {
        $categoryEntity = $this->categoryRepository->findCategoryById($id);

        if (!$categoryEntity) {
            throw new EntityNotFoundException('SuluCategoryBundle:Category', $id);
        }

        $this->em->remove($categoryEntity);
        $this->em->flush();

        // throw a category.delete event
        $event = new CategoryDeleteEvent($categoryEntity);
        $this->eventDispatcher->dispatch(CategoryEvents::CATEGORY_DELETE, $event);
    }

    /**
     * Returns an API-Object for a given category-entity. The API-Object wraps the entity
     * and provides neat getters and setters
     * @param Category $category
     * @param string $locale
     * @return null|CategoryWrapper
     */
    public function getApiObject($category, $locale)
    {
        if ($category instanceof CategoryEntity) {
            return new CategoryWrapper($category, $locale);
        } else {
            return null;
        }
    }

    /**
     * Same as getApiObject, but takes multiple category-entities
     * @param Category[] $categories
     * @param string $locale
     * @return CategoryWrapper[]
     */
    public function getApiObjects($categories, $locale)
    {
        $arrReturn = [];
        foreach ($categories as $category) {
            array_push($arrReturn, $this->getApiObject($category, $locale));
        }
        return $arrReturn;
    }

    /**
     * Returns a user for a given user-id
     * @param $userId
     * @return \Sulu\Component\Security\UserInterface
     */
    private function getUser($userId)
    {
        return $this->userRepository->findUserById($userId);
    }

    /**
     * Creates a new category with given data
     * @param $data
     * @param $user
     * @return CategoryEntity
     */
    private function createCategory($data, $user)
    {
        $categoryEntity = new CategoryEntity();
        $categoryEntity->setCreator($user);
        $categoryEntity->setChanger($user);
        $categoryEntity->setCreated(new \DateTime());
        $categoryEntity->setChanged(new \DateTime());

        $categoryWrapper = $this->getApiObject($categoryEntity, $data['locale']);
        $categoryWrapper->setName($data['name']);
        if (isset($data['meta'])) {
            $categoryWrapper->setMeta($data['meta']);
        }
        if (isset($data['parent'])) {
            $parentEntity = $this->findById($data['parent']);
            $categoryWrapper->setParent($parentEntity);
        }

        $categoryEntity = $categoryWrapper->getEntity();
        $this->em->persist($categoryEntity);
        $this->em->flush();

        return $categoryEntity;
    }

    /**
     * Modifies an existing category with given data
     * @param $data
     * @param $user
     * @return CategoryEntity
     * @throws EntityNotFoundException
     */
    private function modifyCategory($data, $user)
    {
        $categoryEntity = $this->findById($data['id']);
        if (!$categoryEntity) {
            throw new EntityNotFoundException($categoryEntity, $data['id']);
        }

        $categoryEntity->setChanged(new \DateTime());
        $categoryEntity->setChanger($user);

        $categoryWrapper = $this->getApiObject($categoryEntity, $data['locale']);
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
