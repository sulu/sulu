<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use PHPUnit_Framework_Assert;
use Prophecy\Argument;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderCreateEvent;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderEvents;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DoctrineListBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var DoctrineListBuilder
     */
    private $doctrineListBuilder;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var AbstractQuery
     */
    private $query;

    /**
     * @var \ReflectionMethod
     */
    private $findIdsByGivenCriteria;

    /**
     * Result of id subquery.
     *
     * @var array
     */
    private $idResult = [
        ['id' => '1'],
        ['id' => '2'],
        ['id' => '3'],
    ];

    private static $entityName = 'SuluCoreBundle:Example';
    private static $translationEntityName = 'SuluCoreBundle:ExampleTranslation';

    public function setUp()
    {
        $this->entityManager = $this->prophesize(EntityManager::class);

        $this->queryBuilder = $this->prophesize(QueryBuilder::class);

        $this->query = $this->prophesize(AbstractQuery::class);

        $this->entityManager->createQueryBuilder()->willReturn($this->queryBuilder->reveal());

        $this->queryBuilder->from(self::$entityName, self::$entityName)->wilLReturn($this->queryBuilder->reveal());
        $this->queryBuilder->select(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->addGroupBy()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->where(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->setMaxResults(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->getQuery()->willReturn($this->query->reveal());

        $this->queryBuilder->distinct(false)->should(function () {
        });
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->should(function () {
        });
        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldBeCalled();

        $this->query->getArrayResult()->willReturn($this->idResult);
        $this->query->getScalarResult()->willReturn([[3]]);

        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->doctrineListBuilder = new DoctrineListBuilder(
            $this->entityManager->reveal(),
            self::$entityName,
            $this->eventDispatcher->reveal(),
            [PermissionTypes::VIEW => 64]
        );

        $event = new ListBuilderCreateEvent($this->doctrineListBuilder);
        $this->eventDispatcher->dispatch(ListBuilderEvents::LISTBUILDER_CREATE, $event)->willReturn($event);

        $doctrineListBuilderReflectionClass = new \ReflectionClass($this->doctrineListBuilder);
        $this->findIdsByGivenCriteria = $doctrineListBuilderReflectionClass->getMethod('findIdsByGivenCriteria');
        $this->findIdsByGivenCriteria->setAccessible(true);
    }

    public function testSetField()
    {
        $this->doctrineListBuilder->setSelectFields(
            [
                new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName),
                new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName),
            ]
        );

        $this->queryBuilder->addSelect(self::$entityName . '.name AS name_alias')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityName . '.desc AS desc_alias')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testIdSelect()
    {
        $this->queryBuilder->select(self::$entityName . '.id')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());

        $this->doctrineListBuilder->execute();
    }

    public function testPreselectWithNoJoins()
    {
        $this->doctrineListBuilder->addSelectField(
            new DoctrineFieldDescriptor(
                'name',
                'name_alias',
                self::$entityName,
                '',
                [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        self::$entityName . '.translations'
                    ),
                    'anotherEntityName' => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        'anotherEntityName' . '.translations',
                        null,
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER
                    ),
                ]
            )
        );

        // no joins should be made
        $this->queryBuilder->leftJoin(Argument::cetera())->shouldNotBeCalled();
        $this->queryBuilder->innerJoin(Argument::cetera())->shouldNotBeCalled();

        $this->findIdsByGivenCriteria->invoke($this->doctrineListBuilder);
    }

    public function testPreselectWithJoins()
    {
        $this->doctrineListBuilder->addSelectField(
            new DoctrineFieldDescriptor(
                'name',
                'name_alias',
                self::$entityName,
                '',
                [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        self::$entityName . '.translations',
                        null,
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER
                    ),
                    'anotherEntityName' => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        'anotherEntityName' . '.translations',
                        null,
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER
                    ),
                ]
            )
        );

        $this->queryBuilder->innerJoin(
            self::$entityName . '.translations',
            self::$translationEntityName,
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            null
        )->shouldBeCalled();

        $this->queryBuilder->innerJoin(
            'anotherEntityName.translations',
            'anotherEntityName',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            null
        )->shouldBeCalled();

        $this->findIdsByGivenCriteria->invoke($this->doctrineListBuilder);
    }

    public function testPreselectWithConditions()
    {
        $fieldDescriptor = new DoctrineFieldDescriptor(
            'name',
            'name_alias',
            'anotherEntityName',
            '',
            [
                self::$translationEntityName => new DoctrineJoinDescriptor(
                    self::$translationEntityName,
                    self::$entityName . '.translations'
                    ),
                'anotherEntityName' => new DoctrineJoinDescriptor(
                    self::$translationEntityName,
                    'anotherEntityName' . '.translations'
                ),
            ]
        );

        $this->doctrineListBuilder->addSelectField($fieldDescriptor);
        $this->doctrineListBuilder->where($fieldDescriptor, 'test');

        $this->queryBuilder->andWhere(Argument::containingString('anotherEntityName.name = :name_alias'))->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('name_alias'), 'test')->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            self::$entityName . '.translations',
            self::$translationEntityName,
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            null
        )->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            'anotherEntityName.translations',
            'anotherEntityName',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            null
        )->shouldBeCalled();

        $this->findIdsByGivenCriteria->invoke($this->doctrineListBuilder);
    }

    public function testAddField()
    {
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName));
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName));

        $this->queryBuilder->addSelect(self::$entityName . '.name AS name_alias')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityName . '.desc AS desc_alias')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testAddFieldWithJoin()
    {
        $this->doctrineListBuilder->addSelectField(
            new DoctrineFieldDescriptor(
                'desc', 'desc_alias', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                            self::$translationEntityName, self::$entityName . '.translations'
                        ),
                ]
            )
        );

        $this->queryBuilder->addSelect(self::$translationEntityName . '.desc AS desc_alias')->shouldBeCalled();
        $this->queryBuilder->leftJoin(
            self::$entityName . '.translations',
            self::$translationEntityName,
            'WITH',
            null
        )->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSearchFieldWithJoin()
    {
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor(
                'desc', 'desc_alias', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                            self::$translationEntityName, self::$entityName . '.translations'
                        ),
                ]
            )
        );

        $this->queryBuilder->leftJoin(
            self::$entityName . '.translations',
            self::$translationEntityName,
            'WITH',
            null
        )->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSortFieldWithJoin()
    {
        $this->doctrineListBuilder->sort(
            new DoctrineFieldDescriptor(
                'desc', 'desc_alias', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                            self::$translationEntityName, self::$entityName . '.translations'
                        ),
                ]
            )
        );

        $this->queryBuilder->leftJoin(
            self::$entityName . '.translations',
            self::$translationEntityName,
            'WITH',
            null
        )->shouldBeCalled();

        $this->queryBuilder->getDQLPart('select')->willReturn([]);
        // will be called for result (should not be displayed)
        $this->queryBuilder->addSelect('SuluCoreBundle:ExampleTranslation.desc AS HIDDEN desc_alias')->shouldBeCalled();
        // will be called for id query
        $this->queryBuilder->addSelect('SuluCoreBundle:ExampleTranslation.desc AS desc_alias')->shouldBeCalled();
        $this->queryBuilder->addOrderBy('desc_alias', 'ASC')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSearch()
    {
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('desc', 'desc', self::$translationEntityName)
        );
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('name', 'name', self::$entityName)
        );
        $this->doctrineListBuilder->search('value');

        $this->queryBuilder->andWhere(
            '(' . self::$translationEntityName . '.desc LIKE :search OR ' . self::$entityName . '.name LIKE :search)'
        )->shouldBeCalled();
        $this->queryBuilder->setParameter('search', '%value%')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSearchWithPlaceholder()
    {
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('desc', 'desc', self::$translationEntityName)
        );
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('name', 'name', self::$entityName)
        );

        $this->doctrineListBuilder->search('val*e');

        $this->queryBuilder->andWhere(
            '(' . self::$translationEntityName . '.desc LIKE :search OR ' . self::$entityName . '.name LIKE :search)'
        )->shouldBeCalled();
        $this->queryBuilder->setParameter('search', '%val%e%')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSort()
    {
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));

        $this->queryBuilder->getDQLPart('select')->willReturn([]);
        // will be called for result (should not be displayed)
        $this->queryBuilder->addSelect('SuluCoreBundle:Example.desc AS HIDDEN desc')->shouldBeCalled();
        // will be called for id query
        $this->queryBuilder->addSelect('SuluCoreBundle:Example.desc AS desc')->shouldBeCalled();
        $this->queryBuilder->addOrderBy('desc', 'ASC')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSortWithExistingSelect()
    {
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));

        $this->queryBuilder->getDQLPart('select')->willReturn([new Select('SuluCoreBundle:Example.desc AS desc')]);
        // will NOT be called for result (should not be displayed)
        $this->queryBuilder->addSelect('SuluCoreBundle:Example.desc AS HIDDEN desc')->shouldNotBeCalled();
        // will be called for id query
        $this->queryBuilder->addSelect('SuluCoreBundle:Example.desc AS desc')->shouldBeCalled();
        $this->queryBuilder->addOrderBy('desc', 'ASC')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    /**
     * Test if multiple calls to sort with same field descriptor will lead to multiple order by calls.
     */
    public function testSortWithMultipleSort()
    {
        $this->queryBuilder->getDQLPart('select')->willReturn([new Select('SuluCoreBundle:Example.desc AS desc')]);

        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));

        $this->queryBuilder->addSelect('SuluCoreBundle:Example.desc AS desc')->shouldBeCalledTimes(1);
        $this->queryBuilder->addOrderBy('desc', 'ASC')->shouldBeCalledTimes(2);

        $this->doctrineListBuilder->execute();
    }

    /**
     * Test if sort is correnctly overwritten, when field descriptor is provided multiple times.
     */
    public function testChangeSortOrder()
    {
        $this->queryBuilder->getDQLPart('select')->willReturn([new Select('SuluCoreBundle:Example.desc AS desc')]);

        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName), 'ASC');
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName), 'DESC');

        $this->queryBuilder->addSelect('SuluCoreBundle:Example.desc AS desc')->shouldBeCalledTimes(1);
        $this->queryBuilder->addOrderBy('desc', 'DESC')->shouldBeCalledTimes(2);

        $this->doctrineListBuilder->execute();
    }

    public function testSortWithoutDefault()
    {
        // when no sort is applied, results should be orderd by id by default
        $this->queryBuilder->addOrderBy(self::$entityName . '.id', 'ASC')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSortConcat()
    {
        $select = 'CONCAT(SuluCoreBundle:Example.name, CONCAT(\' \', SuluCoreBundle:Example.desc)) AS name_desc';

        $this->doctrineListBuilder->sort(new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineFieldDescriptor('name', 'name', self::$entityName),
                new DoctrineFieldDescriptor('desc', 'desc', self::$entityName),
            ],
            'name_desc'
        ));

        $this->queryBuilder
            ->addSelect($select)
            ->shouldBeCalled();

        $selectExpression = $this->prophesize(Select::class);
        $selectExpression->getParts()->willReturn([$select]);
        $this->queryBuilder->getDQLPart('select')->willReturn([$selectExpression->reveal()]);

        $this->doctrineListBuilder->execute();

        $this->queryBuilder->addOrderBy('name_desc', 'ASC')->shouldHaveBeenCalledTimes(2);
    }

    public function testLimit()
    {
        $this->doctrineListBuilder->limit(5);

        $this->queryBuilder->setMaxResults(5)->shouldBeCalled()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testCount()
    {
        $this->doctrineListBuilder->setSelectFields(
            [
                new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName),
                new DoctrineFieldDescriptor(
                    'desc', 'desc_alias', self::$translationEntityName, 'translation', [
                        self::$translationEntityName => new DoctrineJoinDescriptor(
                                self::$translationEntityName, self::$entityName . '.translations'
                            ),
                    ]
                ),
            ]
        );

        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('desc', 'desc', self::$translationEntityName)
        );
        $this->doctrineListBuilder->search('value');

        $this->doctrineListBuilder->limit(5);

        $this->queryBuilder->andWhere(Argument::cetera())->shouldBeCalled();
        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldNotBeCalled();
        $this->queryBuilder->leftJoin(Argument::cetera())->shouldBeCalledTimes(1);
        $this->queryBuilder->setParameter(Argument::cetera())->shouldBeCalledTimes(1);
        $this->queryBuilder->setMaxResults(Argument::cetera())->shouldNotBeCalled();
        $this->queryBuilder->setFirstResult(Argument::cetera())->shouldNotBeCalled();

        $this->doctrineListBuilder->count();
    }

    public function testSetWhereWithSameName()
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
            'desc_id' => new DoctrineFieldDescriptor('id', 'desc_id', self::$entityName),
        ];

        $filter = [
            'title_id' => 3,
            'desc_id' => 1,
        ];

        $this->queryBuilder->addSelect('SuluCoreBundle:Example.id AS title_id')->shouldBeCalled();
        $this->queryBuilder->addSelect('SuluCoreBundle:Example.id AS desc_id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title'), 3)->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('desc'), 1)->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('SuluCoreBundle:Example.id = :title_id')
        )->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('SuluCoreBundle:Example.id = :desc_id')
        )->shouldBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value);
        }

        $this->assertCount(2, PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'expressions'));
        $expressions = PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'expressions');
        $this->assertEquals(3, $expressions[0]->getValue());
        $this->assertEquals(1, $expressions[1]->getValue());

        $this->assertCount(2, PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'expressions'));
        $this->assertEquals('title_id', $expressions[0]->getFieldName());
        $this->assertEquals('desc_id', $expressions[1]->getFieldName());
        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereWithNull()
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
        ];

        $filter = [
            'title_id' => null,
        ];

        $this->queryBuilder->addSelect('SuluCoreBundle:Example.id AS title_id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), Argument::any())->shouldNotBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value);
        }

        $this->queryBuilder->andWhere('(SuluCoreBundle:Example.id IS NULL)')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereWithNotNull()
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
        ];

        $filter = [
            'title_id' => null,
        ];

        $this->queryBuilder->addSelect('SuluCoreBundle:Example.id AS title_id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), Argument::any())->shouldNotBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value, ListBuilderInterface::WHERE_COMPARATOR_UNEQUAL);
        }

        $this->queryBuilder->andWhere('(SuluCoreBundle:Example.id IS NOT NULL)')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereNot()
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
            'desc_id' => new DoctrineFieldDescriptor('id', 'desc_id', self::$entityName),
        ];

        $filter = [
            'title_id' => 3,
            'desc_id' => 1,
        ];

        $this->queryBuilder->addSelect('SuluCoreBundle:Example.id AS title_id')->shouldBeCalled();
        $this->queryBuilder->addSelect('SuluCoreBundle:Example.id AS desc_id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), 3)->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('desc_id'), 1)->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('SuluCoreBundle:Example.id != :title_id')
        )->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('SuluCoreBundle:Example.id != :desc_id')
        )->shouldBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value, ListBuilderInterface::WHERE_COMPARATOR_UNEQUAL);
        }

        $this->assertCount(2, PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'expressions'));
        $expressions = PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'expressions');
        $this->assertEquals(3, $expressions[0]->getValue());
        $this->assertEquals(1, $expressions[1]->getValue());

        $this->assertCount(2, PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'expressions'));
        $this->assertEquals('title_id', $expressions[0]->getFieldName());
        $this->assertEquals('desc_id', $expressions[1]->getFieldName());
        $this->doctrineListBuilder->execute();
    }

    public function testSetIn()
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('id', 'title_id', self::$entityName);

        $this->queryBuilder->addSelect('SuluCoreBundle:Example.id AS title_id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), [1, 2])->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('SuluCoreBundle:Example.id IN (:title_id')
        )->shouldBeCalled();

        $this->doctrineListBuilder->addSelectField($fieldDescriptor);
        $this->doctrineListBuilder->in($fieldDescriptor, [1, 2]);

        $this->doctrineListBuilder->execute();
    }

    public function testJoinMethods()
    {
        $fieldDescriptors = [
            'id1' => new DoctrineFieldDescriptor(
                    null, null, null, null, [
                        new DoctrineJoinDescriptor(null, null, null, DoctrineJoinDescriptor::JOIN_METHOD_LEFT),
                    ]
                ),
            'id2' => new DoctrineFieldDescriptor(
                    null, null, null, null, [
                        new DoctrineJoinDescriptor(null, null, null, DoctrineJoinDescriptor::JOIN_METHOD_INNER),
                    ]
                ),
        ];

        $this->doctrineListBuilder->setSelectFields($fieldDescriptors);

        $this->queryBuilder->addSelect('. AS ')->shouldBeCalled();

        // not necessary for id join
        $this->queryBuilder->leftJoin(null, 0, 'WITH', null)->shouldBeCalled();
        // called when select ids and for selecting data
        $this->queryBuilder->innerJoin(null, 0, 'WITH', null)->shouldBeCalled();
        $this->queryBuilder->innerJoin(null, 1, 'WITH', null)->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testJoinConditions()
    {
        $fieldDescriptors = [
            'id1' => new DoctrineFieldDescriptor(
                null,
                null,
                null,
                null,
                [
                    self::$entityName . '1' => new DoctrineJoinDescriptor(
                        self::$entityName,
                        null,
                        'field1 = value1',
                        DoctrineJoinDescriptor::JOIN_METHOD_LEFT
                    ),
                ]
            ),
            'id2' => new DoctrineFieldDescriptor(
                null,
                null,
                null,
                null,
                [
                    self::$entityName . '2' => new DoctrineJoinDescriptor(
                        self::$entityName,
                        null,
                        'field2 = value2',
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER,
                        DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_ON
                    ),
                ]
            ),
        ];

        $this->doctrineListBuilder->setSelectFields($fieldDescriptors);

        $this->queryBuilder->addSelect('. AS ')->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            null,
            self::$entityName . '1',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            'field1 = value1'
        )->shouldBeCalled();

        $this->queryBuilder->innerJoin(
            null,
            self::$entityName . '2',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_ON,
            'field2 = value2'
        )->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testGroupBy()
    {
        $nameFieldDescriptor = new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName);

        $this->queryBuilder->addSelect('SuluCoreBundle:Example.name AS name_alias')->shouldBeCalled();
        $this->queryBuilder->groupBy(self::$entityName . '.name')->shouldBeCalledTimes(2);

        $this->doctrineListBuilder->setSelectFields(
            [
                $nameFieldDescriptor,
            ]
        );

        $this->doctrineListBuilder->addGroupBy($nameFieldDescriptor);

        $this->doctrineListBuilder->execute();
    }

    public function testBetween()
    {
        $nameFieldDescriptor = new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName);

        $this->queryBuilder->addSelect('SuluCoreBundle:Example.name AS name_alias')->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('SuluCoreBundle:Example.name BETWEEN :name_alias')
        )->shouldBeCalledTimes(1);
        $this->queryBuilder->setParameter(Argument::containingString('name_alias'), 0)->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('name_alias'), 1)->shouldBeCalled();

        $this->doctrineListBuilder->setSelectFields(
            [
                $nameFieldDescriptor,
            ]
        );

        $this->doctrineListBuilder->between($nameFieldDescriptor, [0, 1]);

        $this->doctrineListBuilder->execute();
    }

    public function testDistinct()
    {
        $this->doctrineListBuilder->distinct(true);

        $this->queryBuilder->distinct(true)->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testNoDistinct()
    {
        $this->queryBuilder->distinct(false)->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testIdField()
    {
        $idField = $this->prophesize(DoctrineFieldDescriptorInterface::class);
        $idField->getSelect()->willReturn('example.id');

        $this->doctrineListBuilder->setIdField($idField->reveal());

        $this->queryBuilder->select('example.id')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->where('example.id IN (:ids)')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());

        $this->doctrineListBuilder->execute();
    }

    public function testNoIdField()
    {
        $this->queryBuilder
            ->select('SuluCoreBundle:Example.id')
            ->shouldBeCalled()
            ->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder
            ->where('SuluCoreBundle:Example.id IN (:ids)')
            ->shouldBeCalled()
            ->willReturn($this->queryBuilder->reveal());

        $this->doctrineListBuilder->execute();
    }

    public function testSetPermissionCheck()
    {
        $user = $this->prophesize(User::class);
        $role = $this->prophesize(Role::class);
        $role->getId()->willReturn(1);
        $user->getRoleObjects()->willReturn([$role->reveal()]);

        $this->doctrineListBuilder->setPermissionCheck($user->reveal(), PermissionTypes::VIEW);

        $this->queryBuilder->leftJoin(
            AccessControl::class,
            'accessControl',
            'WITH',
            'accessControl.entityClass = :entityClass AND accessControl.entityId = SuluCoreBundle:Example.id'
        )->shouldBeCalled();
        $this->queryBuilder->leftJoin('accessControl.role', 'role')->shouldBeCalled();
        $this->queryBuilder->andWhere(
            'BIT_AND(accessControl.permissions, :permission) = :permission OR accessControl.permissions IS NULL'
        )->shouldBeCalled();
        $this->queryBuilder->andWhere('role.id IN(:roleIds) OR role.id IS NULL')->shouldBeCalled();
        $this->queryBuilder->setParameter('roleIds', [1])->shouldBeCalled();
        $this->queryBuilder->setParameter('entityClass', 'SuluCoreBundle:Example')->shouldBeCalled();
        $this->queryBuilder->setParameter('permission', 64)->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }
}
