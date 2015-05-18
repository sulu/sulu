<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

use PHPUnit_Framework_Assert;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;

class DoctrineListBuilderTest extends \PHPUnit_Framework_TestCase
{
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
            ->setMethods(array('execute', 'getSingleScalarResult'))
            ->getMockForAbstractClass();

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->any())->method('select')->willReturnSelf();
        $this->queryBuilder->expects($this->any())->method('addGroupBy')->willReturnSelf();

        $this->queryBuilder->expects($this->any())->method('setMaxResults')->willReturnSelf();
        $this->queryBuilder->expects($this->any())->method('getQuery')->willReturn($this->query);

        $this->queryBuilder->expects($this->once())->method('from')->with(
            self::$entityName, self::$entityName
        )->willReturnSelf();

        $this->doctrineListBuilder = new DoctrineListBuilder($this->em, self::$entityName);
    }

    public function testSetField()
    {
        $this->doctrineListBuilder->setFields(
            array(
                new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName),
                new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName),
            )
        );

        $this->queryBuilder->expects($this->at(1))->method('addSelect')->with(
            self::$entityName . '.name AS name_alias'
        );

        $this->queryBuilder->expects($this->at(2))->method('addSelect')->with(
            self::$entityName . '.desc AS desc_alias'
        );

        $this->doctrineListBuilder->execute();
    }

    public function testAddField()
    {
        $this->doctrineListBuilder->addField(new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName));
        $this->doctrineListBuilder->addField(new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName));

        $this->queryBuilder->expects($this->at(1))->method('addSelect')->with(
            self::$entityName . '.name AS name_alias'
        );

        $this->queryBuilder->expects($this->at(2))->method('addSelect')->with(
            self::$entityName . '.desc AS desc_alias'
        );

        $this->doctrineListBuilder->execute();
    }

    public function testAddFieldWithJoin()
    {
        $this->doctrineListBuilder->addField(
            new DoctrineFieldDescriptor(
                'desc', 'desc_alias', self::$translationEntityName, 'translation', array(
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                            self::$translationEntityName, self::$entityName . '.translations'
                        ),
                )
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
                'desc', 'desc_alias', self::$translationEntityName, 'translation', array(
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                            self::$translationEntityName, self::$entityName . '.translations'
                        ),
                )
            )
        );

        $this->queryBuilder->expects($this->once())->method('leftJoin')->with(
            self::$entityName . '.translations', self::$translationEntityName
        );

        $this->doctrineListBuilder->execute();
    }

    public function testSortFieldWithJoin()
    {
        $this->doctrineListBuilder->sort(
            new DoctrineFieldDescriptor(
                'desc', 'desc_alias', self::$translationEntityName, 'translation', array(
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                            self::$translationEntityName, self::$entityName . '.translations'
                        ),
                )
            )
        );

        $this->queryBuilder->expects($this->once())->method('leftJoin')->with(
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
        $this->queryBuilder->expects($this->once())->method('setParameter')->with('search', '%value%');

        $this->doctrineListBuilder->execute();
    }

    public function testSort()
    {
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));

        $this->queryBuilder->expects($this->once())->method('orderBy')->with(self::$entityName . '.desc', 'ASC');

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
        $this->doctrineListBuilder->setFields(
            array(
                new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName),
                new DoctrineFieldDescriptor(
                    'desc', 'desc_alias', self::$translationEntityName, 'translation', array(
                        self::$translationEntityName => new DoctrineJoinDescriptor(
                                self::$translationEntityName, self::$entityName . '.translations'
                            ),
                    )
                ),
            )
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
        $fieldDescriptors = array(
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
            'desc_id' => new DoctrineFieldDescriptor('id', 'desc_id', self::$entityName),
        );

        $filter = array(
            'title_id' => 3,
            'desc_id' => 1,
        );

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value);
        }

        $this->assertCount(2, PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'whereValues'));
        $whereValues = PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'whereValues');
        $this->assertEquals(3, $whereValues['title_id']);
        $this->assertEquals(1, $whereValues['desc_id']);

        $this->assertCount(2, PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'whereFields'));
        $whereFields = PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'whereFields');
        $this->assertEquals($fieldDescriptors['title_id'], $whereFields['title_id']);
        $this->assertEquals($fieldDescriptors['desc_id'], $whereFields['desc_id']);

        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereWithNull()
    {
        $fieldDescriptors = array(
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
        );

        $filter = array(
            'title_id' => null,
        );

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value);
        }

        $this->queryBuilder->expects($this->once())->method('andWhere')->with('(SuluCoreBundle:Example.id IS NULL)');

        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereWithNotNull()
    {
        $fieldDescriptors = array(
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
        );

        $filter = array(
            'title_id' => null,
        );

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->whereNot($fieldDescriptors[$key], $value);
        }

        $this->queryBuilder->expects($this->once())->method('andWhere')->with('(SuluCoreBundle:Example.id IS NOT NULL)');

        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereNot()
    {
        $fieldDescriptors = array(
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
            'desc_id' => new DoctrineFieldDescriptor('id', 'desc_id', self::$entityName),
        );

        $filter = array(
            'title_id' => 3,
            'desc_id' => 1,
        );

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->whereNot($fieldDescriptors[$key], $value);
        }

        $this->assertCount(2, PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'whereNotValues'));
        $whereNotValues = PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'whereNotValues');
        $this->assertEquals(3, $whereNotValues['title_id']);
        $this->assertEquals(1, $whereNotValues['desc_id']);

        $this->assertCount(2, PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'whereNotFields'));
        $whereNotFields = PHPUnit_Framework_Assert::readAttribute($this->doctrineListBuilder, 'whereNotFields');
        $this->assertEquals($fieldDescriptors['title_id'], $whereNotFields['title_id']);
        $this->assertEquals($fieldDescriptors['desc_id'], $whereNotFields['desc_id']);

        $this->doctrineListBuilder->execute();
    }

    public function testSetIn()
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('id', 'title_id', self::$entityName);

        $this->doctrineListBuilder->addField($fieldDescriptor);
        $this->doctrineListBuilder->in($fieldDescriptor, array(1, 2));

        $this->queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->with('(SuluCoreBundle:Example.id IN (:title_id))');

        $this->doctrineListBuilder->execute();
    }

    public function testJoinMethods()
    {
        $fieldDescriptors = array(
            'id1' => new DoctrineFieldDescriptor(
                    null, null, null, null, array(
                        new DoctrineJoinDescriptor(null, null, null, DoctrineJoinDescriptor::JOIN_METHOD_LEFT),
                    )
                ),
            'id2' => new DoctrineFieldDescriptor(
                    null, null, null, null, array(
                        new DoctrineJoinDescriptor(null, null, null, DoctrineJoinDescriptor::JOIN_METHOD_INNER),
                    )
                ),
        );

        $this->doctrineListBuilder->setFields($fieldDescriptors);

        $this->queryBuilder->expects($this->once())->method('leftJoin');
        $this->queryBuilder->expects($this->once())->method('innerJoin');

        $this->doctrineListBuilder->execute();
    }

    public function testJoinConditions()
    {
        $fieldDescriptors = array(
            'id1' => new DoctrineFieldDescriptor(
                    null,
                    null,
                    null,
                    null,
                    array(
                        self::$entityName . '1' => new DoctrineJoinDescriptor(
                            self::$entityName,
                            null,
                            'field1 = value1',
                            DoctrineJoinDescriptor::JOIN_METHOD_LEFT
                        ),
                    )
                ),
            'id2' => new DoctrineFieldDescriptor(
                    null,
                    null,
                    null,
                    null,
                    array(
                        self::$entityName . '2' => new DoctrineJoinDescriptor(
                            self::$entityName,
                            null,
                            'field2 = value2',
                            DoctrineJoinDescriptor::JOIN_METHOD_INNER,
                            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_ON
                        ),
                    )
                ),
        );

        $this->doctrineListBuilder->setFields($fieldDescriptors);

        $this->queryBuilder->expects($this->once())->method('leftJoin')->with(
            null,
            self::$entityName . '1',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            'field1 = value1'
        );
        $this->queryBuilder->expects($this->once())->method('innerJoin')->with(
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

        $this->doctrineListBuilder->setFields(
            array(
                $nameFieldDescriptor,
            )
        );

        $this->doctrineListBuilder->addGroupBy($nameFieldDescriptor);

        $this->queryBuilder->expects($this->at(1))->method('groupBy')->with(self::$entityName . '.name');

        $this->doctrineListBuilder->execute();
    }

    public function testBetween()
    {
        $nameFieldDescriptor = new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName);

        $this->doctrineListBuilder->setFields(
            array(
                $nameFieldDescriptor,
            )
        );

        $this->doctrineListBuilder->between($nameFieldDescriptor, array(0, 1));

        $this->queryBuilder->expects($this->once())->method('andWhere')->with(
            '(SuluCoreBundle:Example.name BETWEEN :name_alias1 AND :name_alias2)'
        );

        $this->queryBuilder->expects($this->at(1))->method('setParameter')->with('name_alias1', 0);
        $this->queryBuilder->expects($this->at(2))->method('setParameter')->with('name_alias2', 1);

        $this->doctrineListBuilder->execute();
    }
}
