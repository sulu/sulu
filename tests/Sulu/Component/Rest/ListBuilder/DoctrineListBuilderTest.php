<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use Sulu\Component\Rest\ListBuilder\FieldDescriptor\DoctrineFieldDescriptor;

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
            ->setMethods(array('execute'))
            ->getMockForAbstractClass();

        $this->em->expects($this->once())->method('createQueryBuilder')->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->any())->method('setMaxResults')->willReturnSelf();
        $this->queryBuilder->expects($this->once())->method('getQuery')->willReturn($this->query);

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
                'desc', 'desc_alias', self::$translationEntityName, array(
                self::$translationEntityName => self::$entityName . '.translations'
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
            new DoctrineFieldDescriptor('desc', 'desc_alias', self::$translationEntityName, array(
                self::$translationEntityName => self::$entityName . '.translations'
            ))
        );

        $this->queryBuilder->expects($this->once())->method('leftJoin')->with(
            self::$entityName . '.translations', self::$translationEntityName
        );

        $this->doctrineListBuilder->execute();
    }

    public function testSortFieldWithJoin()
    {
        $this->doctrineListBuilder->sort(
            new DoctrineFieldDescriptor('desc', 'desc_alias', self::$translationEntityName, array(
                self::$translationEntityName => self::$entityName . '.translations'
            ))
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

        $this->queryBuilder->expects($this->at(1))->method('orWhere')->with(
            self::$translationEntityName . '.desc LIKE :search'
        );
        $this->queryBuilder->expects($this->at(2))->method('orWhere')->with(
            self::$entityName . '.name LIKE :search'
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
}
