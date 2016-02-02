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

use PHPUnit_Framework_Assert;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderCreateEvent;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderEvents;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

class DoctrineListBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var DoctrineListBuilder
     */
    private $doctrineListBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $queryBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute', 'getSingleScalarResult', 'getArrayResult'])
            ->getMockForAbstractClass();

        $this->em->expects($this->any())->method('createQueryBuilder')->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->any())->method('select')->willReturnSelf();
        $this->queryBuilder->expects($this->any())->method('addGroupBy')->willReturnSelf();
        $this->queryBuilder->expects($this->any())->method('where')->willReturnSelf();

        $this->queryBuilder->expects($this->any())->method('setMaxResults')->willReturnSelf();
        $this->queryBuilder->expects($this->any())->method('getQuery')->willReturn($this->query);

        $this->query->expects($this->any())->method('getArrayResult')->willReturn($this->idResult);

        $this->queryBuilder->expects($this->any())->method('from')->with(
            self::$entityName, self::$entityName
        )->willReturnSelf();

        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineListBuilder = new DoctrineListBuilder($this->em, self::$entityName, $this->eventDispatcher);

        $event = new ListBuilderCreateEvent($this->doctrineListBuilder);
        $this->eventDispatcher->expects($this->any())->method('dispatch')->with(
            ListBuilderEvents::LISTBUILDER_CREATE, $event
        )->willReturn($event);

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

        $this->queryBuilder->expects($this->exactly(2))->method('addSelect')->withConsecutive(
            [
                self::$entityName . '.name AS name_alias',
            ],
            [
                self::$entityName . '.desc AS desc_alias',
            ]
        );

        $this->doctrineListBuilder->execute();
    }

    public function testIdSelect()
    {
        $this->queryBuilder->expects($this->at(1))->method('select')->with(
            self::$entityName . '.id'
        );

        $this->queryBuilder->expects($this->exactly(1))->method('setParameter')->withConsecutive(
            ['ids', ['1', '2', '3']]
        );

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
        $this->queryBuilder->expects($this->never())->method('leftJoin')->withAnyParameters();
        $this->queryBuilder->expects($this->never())->method('innerJoin')->withAnyParameters();

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

        $this->queryBuilder->expects($this->exactly(2))->method('innerJoin')->withConsecutive(
            [
                self::$entityName . '.translations',
                self::$translationEntityName,
                DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,

            ],
            [
                'anotherEntityName' . '.translations',
                'anotherEntityName',
                DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            ]
        );

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

        $this->queryBuilder->expects($this->exactly(2))->method('leftJoin')->withConsecutive(
            [
                self::$entityName . '.translations',
                self::$translationEntityName,
                DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,

            ],
            [
                'anotherEntityName' . '.translations',
                'anotherEntityName',
                DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            ]
        );

        $this->findIdsByGivenCriteria->invoke($this->doctrineListBuilder);
    }

    public function testAddField()
    {
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName));
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName));

        $this->queryBuilder->expects($this->exactly(2))->method('addSelect')->withConsecutive(
            [
                self::$entityName . '.name AS name_alias',
            ],
            [
                self::$entityName . '.desc AS desc_alias',
            ]
        );

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

        $this->queryBuilder->expects($this->once())->method('addSelect')->with(
            self::$translationEntityName . '.desc AS desc_alias'
        );

        $this->queryBuilder->expects($this->once())->method('leftJoin')->with(
            self::$entityName . '.translations', self::$translationEntityName
        );

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

        $this->queryBuilder->expects($this->exactly(2))->method('leftJoin')->with(
            self::$entityName . '.translations', self::$translationEntityName
        );

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

        $this->queryBuilder->expects($this->exactly(2))->method('leftJoin')->with(
            self::$entityName . '.translations', self::$translationEntityName
        );

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

        $this->queryBuilder->expects($this->once())->method('andWhere')->with(
            '(' . self::$translationEntityName . '.desc LIKE :search OR ' . self::$entityName . '.name LIKE :search)'
        );
        // 2 calls: one for setting IDs in subquery and one for setting values
        $this->queryBuilder->expects($this->exactly(2))->method('setParameter')->withConsecutive(
            ['search', '%value%']
        );

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

        $this->queryBuilder->expects($this->once())->method('andWhere')->with(
            '(' . self::$translationEntityName . '.desc LIKE :search OR ' . self::$entityName . '.name LIKE :search)'
        );
        // 2 calls: one for setting IDs in subquery and one for setting values
        $this->queryBuilder->expects($this->exactly(2))->method('setParameter')->withConsecutive(
            ['search', '%val%e%']
        );

        $this->doctrineListBuilder->execute();
    }

    public function testSort()
    {
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));

        $this->queryBuilder->expects($this->exactly(2))->method('addOrderBy')->with(self::$entityName . '.desc', 'ASC');

        $this->doctrineListBuilder->execute();
    }

    public function testSortWithoutDefault()
    {
        // when no sort is applied, results should be orderd by id by default
        $this->queryBuilder->expects($this->exactly(2))->method('addOrderBy')->with(self::$entityName . '.id', 'ASC');

        $this->doctrineListBuilder->execute();
    }

    public function testLimit()
    {
        $this->doctrineListBuilder->limit(5);

        $this->queryBuilder->expects($this->once())->method('setMaxResults')->with(5);

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

        $this->queryBuilder->expects($this->never())->method('orderBy');
        $this->queryBuilder->expects($this->exactly(1))->method('leftJoin');
        $this->queryBuilder->expects($this->exactly(1))->method('setParameter');
        $this->queryBuilder->expects($this->never())->method('setMaxResults');
        $this->queryBuilder->expects($this->never())->method('setFirstResult');

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

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value);
        }

        $this->queryBuilder->expects($this->once())->method('andWhere')->with('(SuluCoreBundle:Example.id IS NULL)');

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

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value, ListBuilderInterface::WHERE_COMPARATOR_UNEQUAL);
        }

        $this->queryBuilder->expects($this->once())->method('andWhere')->with('(SuluCoreBundle:Example.id IS NOT NULL)');

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

        $this->doctrineListBuilder->addSelectField($fieldDescriptor);
        $this->doctrineListBuilder->in($fieldDescriptor, [1, 2]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->withAnyParameters();

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

        // not necessary for id join
        $this->queryBuilder->expects($this->once())->method('leftJoin');
        // called when select ids and for selecting data
        $this->queryBuilder->expects($this->exactly(2))->method('innerJoin');

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

        $this->queryBuilder->expects($this->once())->method('leftJoin')->with(
            null,
            self::$entityName . '1',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            'field1 = value1'
        );
        $this->queryBuilder->expects($this->exactly(2))->method('innerJoin')->with(
            null,
            self::$entityName . '2',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_ON,
            'field2 = value2'
        );

        $this->doctrineListBuilder->execute();
    }

    public function testGroupBy()
    {
        $nameFieldDescriptor = new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName);

        $this->doctrineListBuilder->setSelectFields(
            [
                $nameFieldDescriptor,
            ]
        );

        $this->doctrineListBuilder->addGroupBy($nameFieldDescriptor);

        $this->queryBuilder->expects($this->once())->method('groupBy')->with(self::$entityName . '.name');

        $this->doctrineListBuilder->execute();
    }

    public function testBetween()
    {
        $nameFieldDescriptor = new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName);

        $this->doctrineListBuilder->setSelectFields(
            [
                $nameFieldDescriptor,
            ]
        );

        $this->doctrineListBuilder->between($nameFieldDescriptor, [0, 1]);

        $this->queryBuilder->expects($this->once())->method('andWhere')->withAnyParameters();

        $this->queryBuilder->expects($this->at(1))->method('setParameter')->withAnyParameters();
        $this->queryBuilder->expects($this->at(2))->method('setParameter')->withAnyParameters();

        $this->doctrineListBuilder->execute();
    }
}
