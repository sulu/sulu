<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Filter;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ResourceBundle\Api\ConditionGroup;
use Sulu\Bundle\ResourceBundle\Api\Condition;
use Sulu\Bundle\ResourceBundle\Entity\Condition as ConditionEntity;
use Sulu\Bundle\ResourceBundle\Entity\ConditionGroup as ConditionGroupEntity;
use Sulu\Bundle\ResourceBundle\Api\Filter;
use Sulu\Bundle\ResourceBundle\Entity\ConditionGroupRepositoryInterface;
use Sulu\Bundle\ResourceBundle\Entity\Filter as FilterEntity;
use Sulu\Bundle\ResourceBundle\Entity\FilterRepositoryInterface;
use Sulu\Bundle\ResourceBundle\Filter\Exception\FilterDependencyNotFoundException;
use Sulu\Bundle\ResourceBundle\Filter\Exception\FilterNotFoundException;
use Sulu\Bundle\ResourceBundle\Filter\Exception\MissingConditionAttributeException;
use Sulu\Bundle\ResourceBundle\Filter\Exception\MissingFilterAttributeException;
use Sulu\Component\Persistence\RelationTrait;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;

/**
 * Class FilterManager
 * @package Sulu\Bundle\ResourceBundle\Filter
 */
class FilterManager implements FilterManagerInterface
{
    use RelationTrait;

    protected static $filterEntityName = 'SuluResourceBundle:Filter';
    protected static $conditionGroupEntityName = 'SuluResourceBundle:ConditionGroup';
    protected static $conditionEntityName = 'SuluResourceBundle:Condition';
    protected static $filterTranslationEntityName = 'SuluResourceBundle:FilterTranslation';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var FilterRepositoryInterface
     */
    protected $filterRepository;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var ConditionGroupRepositoryInterface
     */
    protected $conditionGroupRepository;

    public function __construct(
        $em,
        FilterRepositoryInterface $filterRepo,
        UserRepositoryInterface $userRepository,
        ConditionGroupRepositoryInterface $conditionGroupRepository
    ) {
        $this->em = $em;
        $this->filterRepository = $filterRepo;
        $this->userRepository = $userRepository;
        $this->conditionGroupRepository = $conditionGroupRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldDescriptors($locale)
    {
        $fieldDescriptors = array();
        $fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$filterEntityName,
            'public.id',
            array(),
            true
        );
        $fieldDescriptors['name'] = new DoctrineFieldDescriptor(
            'name',
            'name',
            self::$filterTranslationEntityName,
            'resource.filter.name',
            array(
                self::$filterTranslationEntityName => new DoctrineJoinDescriptor(
                    self::$filterTranslationEntityName,
                    self::$filterEntityName.'.translations',
                    self::$filterTranslationEntityName.'.locale = \''.$locale.'\''
                ),
            )
        );
        $fieldDescriptors['andCombination'] = new DoctrineFieldDescriptor(
            'andCombination',
            'andCombination',
            self::$filterEntityName,
            'resource.filter.andCombination',
            array(),
            true
        );
        $fieldDescriptors['entityName'] = new DoctrineFieldDescriptor(
            'entityName',
            'entityName',
            self::$filterEntityName,
            'resource.filter.entityName',
            array(),
            true
        );

        return $fieldDescriptors;
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdAndLocale($id, $locale)
    {
        $filter = $this->filterRepository->findByIdAndLocale($id, $locale);

        return new Filter($filter, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function findAllByLocale($locale)
    {
        $filters = $this->filterRepository->findAllByLocale($locale);
        array_walk(
            $filters,
            function (&$filter) use ($locale) {
                $filter = new Filter($filter, $locale);
            }
        );

        return $filters;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id)
    {
        $filter = $this->filterRepository->findById($id);
        if (!$filter) {
            throw new FilterNotFoundException($id);
        }
        $this->em->remove($filter);
        $this->em->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function save(array $data, $locale, $userId, $id = null)
    {
        $user = $this->userRepository->findUserById($userId);

        if ($id) {
            $filter = $this->filterRepository->findByIdAndLocale($id, $locale);
            if (!$filter) {
                throw new FilterNotFoundException($id);
            }
            $filter = new Filter($filter, $locale);
        } else {
            $filter = new Filter(new FilterEntity(), $locale);
            $filter->setCreated(new \DateTime());
            $filter->setChanged(new \DateTime());
            $filter->setCreator($user);
        }
        $this->checkData($data, $id === null);
        $user = $this->userRepository->findUserById($userId);

        // set direct properties and translation
        $filter->setChanged(new \DateTime());
        $filter->setChanger($user);
        $filter->setName($this->getProperty($data, 'name', $filter->getName()));
        $filter->setEntityName($this->getProperty($data, 'entityName', $filter->getEntityName()));
        $filter->setAndCombination($this->getProperty($data, 'entityName', $filter->getAndCombination()));
        $filter->setChanger($user);
        $filter->setChanged(new \DateTime());

        // update condition groups and conditions
        if (isset($data['conditionGroups'])) {
//            if (isset($data['id']) && ($filter->getId() == $data['id'])) {
//                $compare = function (ConditionGroup $conditionGroup, $data) {
//                    if (isset($data['id'])) {
//                        return $data['id'] == $conditionGroup->getId();
//                    } else {
//                        return $this->conditionGroupHasChanged($data, $conditionGroup);
//                    }
//                };
//            } else {
//                $compare = function (ConditionGroup $conditionGroup, $data) {
//                    return $this->conditionGroupHasChanged($data, $conditionGroup);
//                };
//            }

            $get = function (ConditionGroup $conditionGroup) {
                return $conditionGroup->getId();
            };
            $add = function ($conditionGroupData) use ($filter, $locale) {
                return $this->addConditionGroup($filter, $locale, $conditionGroupData);
            };
            $delete = function (ConditionGroup $conditionGroup) use ($filter) {
                $filter->removeConditionGroup($conditionGroup);

                return true;
            };
            $update = function (ConditionGroup $conditionGroup, $matchedEntry, $locale) {
                return $this->updateConditionGroup($conditionGroup, $matchedEntry, $locale);
            };
            $this->processSubEntities(
                $filter->getEntity()->getConditionGroups(),
                $data['conditionGroups'],
                $get,
                $add,
                $update,
                $delete
            );

//            $this->compareEntitiesWithData(
//                $filter->getConditionGroups(),
//                $data['conditionGroups'],
//                $compare,
//                $add,
//                $update,
//                $delete
//            );
        }

        $this->em->flush();

        return $filter;
    }

//    /**
//     * Checks if the condition group has changed
//     *
//     * @param array $data
//     * @param ConditionGroup $conditionGroup
//     * @return bool
//     */
//    private function conditionGroupHasChanged($data, $conditionGroup)
//    {
//        if(count($data['conditions']) === count($conditionGroup->getConditions())){
//            foreach()
//        }
//        return false;
//    }

    /**
     * Updates the given condition group with the values from the given array
     *
     * @param ConditionGroup $conditionGroup
     * @param array $matchedEntry
     * @param string $locale
     * @return bool
     * @throws FilterDependencyNotFoundException
     */
    private function updateConditionGroup(ConditionGroup $conditionGroup, $matchedEntry, $locale)
    {
        if (isset($matchedEntry['conditions'])) {

            foreach ($matchedEntry['conditions'] as $conditionData) {
                /** @var ConditionEntity $conditionEntity */
                $conditionEntity = $this->conditionGroupRepository->findById($conditionData['id']);
                if (!$conditionEntity) {
                    throw new FilterDependencyNotFoundException(
                        self::$conditionEntityName,
                        $conditionData['id']
                    );
                }
                $condition = new Condition($conditionEntity, $locale);
                $condition->setField($this->getProperty($conditionData, 'field', $condition->getField()));
                $condition->setOperator($this->getProperty($conditionData, 'operator', $condition->getOperator()));
                $condition->setValue($this->getProperty($conditionData, 'value', $condition->getValue()));
                $condition->setType($this->getProperty($conditionData, 'type', $condition->getType()));
                $condition->setConditionGroup($conditionGroup);
                $conditionGroup->addCondition($condition);
            }

        }

        return true;
    }

    /**
     * Adds a condition group to the given filter
     *
     * @param Filter $filter The filter to add the condition group to
     * @param string $locale
     * @param array $conditionGroupData The array containing the data for the additional condition group
     * @return bool
     * @throws EntityIdAlreadySetException
     * @throws FilterDependencyNotFoundException
     */
    protected function addConditionGroup(Filter $filter, $locale, $conditionGroupData)
    {
        if (isset($conditionGroupData['id'])) {
            throw new EntityIdAlreadySetException(self::$conditionGroupEntityName, $conditionGroupData['id']);
        } elseif ($conditionGroupData['conditions']) {

            $conditionGroup = new ConditionGroup(new ConditionGroupEntity(), $locale);
            $conditionGroup->setFilter($filter);

            foreach ($conditionGroupData['conditions'] as $conditionData) {
                if ($conditionData['id']) {
                    throw new EntityIdAlreadySetException(self::$conditionEntityName, $conditionData['id']);
                } elseif ($this->isValidConditionData($conditionData)) {
                    $condition = new Condition(new ConditionEntity(), $locale);
                    $condition->setValue($conditionData['value']);
                    $condition->setType($conditionData['type']);
                    $condition->setOperator($conditionData['operator']);
                    $condition->setField($conditionData['field']);
                    $condition->setConditionGroup($conditionGroup);
                    $conditionGroup->addCondition($condition);
                    $this->em->persist($condition);
                }
            }

            $filter->addConditionGroup($conditionGroup);
            $this->em->persist($conditionGroup);
        }

        return true;
    }

    /**
     * Returns the entry from the data with the given key, or the given default value, if the key does not exist
     *
     * @param array $data
     * @param string $key
     * @param string $default
     * @return mixed
     */
    protected function getProperty(array $data, $key, $default = null)
    {
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    /**
     * Checks if the given data is correct
     *
     * @param array $data The data to check
     * @param boolean $create Defines if check is for new or already existing data
     */
    protected function checkData($data, $create)
    {
        // TODO adjust for filter
        $this->checkDataSet($data, 'type', $create) && $this->checkDataSet($data['type'], 'id', $create);
        $this->checkDataSet($data, 'status', $create) && $this->checkDataSet($data['status'], 'id', $create);
    }

    /**
     * Checks if data for the given key is set correctly
     *
     * @param array $data The array with the data
     * @param string $key The array key to check
     * @param bool $create Defines if the is for new or already existing data
     * @return bool
     * @throws Exception\MissingFilterAttributeException
     */
    private function checkDataSet(array $data, $key, $create)
    {
        $keyExists = array_key_exists($key, $data);
        if (($create && !($keyExists && $data[$key] !== null)) || (!$keyExists || $data[$key] === null)) {
            throw new MissingFilterAttributeException($key);
        }

        return $keyExists;
    }

    /**
     * Checks if the given data is correct for a condition
     *
     * @param array $data The data to check
     * @return bool
     * @throws MissingConditionAttributeException
     */
    private function isValidConditionData($data)
    {
        if (array_key_exists('field', $data)) {
            throw new MissingConditionAttributeException('field');
        }
        if (array_key_exists('operator', $data)) {
            throw new MissingConditionAttributeException('operator');
        }
        if (array_key_exists('type', $data)) {
            throw new MissingConditionAttributeException('type');
        }
        if (array_key_exists('value', $data)) {
            throw new MissingConditionAttributeException('value');
        }

        return true;
    }
}
