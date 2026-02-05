<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;
use Sulu\Component\Rest\Exception\InvalidSearchException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCountFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderCreateEvent;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderEvents;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineIsNotNullExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineIsNullExpression;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeInterface;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeRegistry;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\SinglePropertyMetadata;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DoctrineListBuilderTest extends TestCase
{
    use ProphecyTrait;
    use ReadObjectAttributeTrait;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $eventDispatcher;

    /**
     * @var ObjectProphecy<FilterTypeRegistry>
     */
    private $filterTypeRegistry;

    /**
     * @var DoctrineListBuilder
     */
    private $doctrineListBuilder;

    /**
     * @var ObjectProphecy<EntityManager>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<ClassMetadata<object>>
     */
    private $classMetadata;

    /**
     * @var ObjectProphecy<QueryBuilder>
     */
    private $queryBuilder;

    /**
     * @var ObjectProphecy<AbstractQuery>
     */
    private $query;

    /**
     * @var ObjectProphecy<SystemStoreInterface>
     */
    private $systemStore;

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

    /**
     * @var class-string
     *
     * @phpstan-ignore property.defaultValue
     */
    private static $entityName = 'Sulu\Bundle\CoreBundle\Entity\Example';

    /** @var string */
    private static $entityNameAlias = 'Sulu_Bundle_CoreBundle_Entity_Example';

    /** @var string */
    private static $translationEntityName = 'Sulu\Bundle\CoreBundle\Entity\ExampleTranslation';

    /** @var string */
    private static $translationEntityNameAlias = 'Sulu_Bundle_CoreBundle_Entity_ExampleTranslation';

    public function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManager::class);
        $this->filterTypeRegistry = $this->prophesize(FilterTypeRegistry::class);
        $this->queryBuilder = $this->prophesize(QueryBuilder::class);
        $this->query = $this->prophesize(AbstractQuery::class); // @phpstan-ignore-line assign.propertyType
        $this->classMetadata = $this->prophesize(ClassMetadata::class); // @phpstan-ignore-line assign.propertyType

        $this->entityManager->createQueryBuilder()->willReturn($this->queryBuilder->reveal());
        $this->entityManager->getClassMetadata(Argument::any())
            ->willReturn($this->classMetadata->reveal());

        $this->queryBuilder->from(self::$entityName, self::$entityNameAlias)->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->select(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->addGroupBy()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->where(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->setMaxResults(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->getQuery()->willReturn($this->query->reveal());
        $this->queryBuilder->getDQL()->willReturn('');

        $this->queryBuilder->distinct(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->should(function() {});
        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldBeCalled();

        $this->query->getArrayResult()->willReturn($this->idResult);
        $this->query->getScalarResult()->willReturn([[3]]);

        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->systemStore = $this->prophesize(SystemStoreInterface::class);
        $this->systemStore->getSystem()->willReturn('Sulu');

        $this->doctrineListBuilder = new DoctrineListBuilder(
            $this->entityManager->reveal(),
            self::$entityName, // @phpstan-ignore-line
            $this->filterTypeRegistry->reveal(),
            $this->eventDispatcher->reveal(),
            [PermissionTypes::VIEW => 64],
            new AccessControlQueryEnhancer($this->systemStore->reveal(), $this->entityManager->reveal())
        );
        $this->doctrineListBuilder->limit(10);
        $this->queryBuilder->setFirstResult(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->setMaxResults(Argument::any())->willReturn($this->queryBuilder->reveal());

        $event = new ListBuilderCreateEvent($this->doctrineListBuilder);
        $this->eventDispatcher->dispatch($event, ListBuilderEvents::LISTBUILDER_CREATE)->willReturn($event);

        $doctrineListBuilderReflectionClass = new \ReflectionClass($this->doctrineListBuilder);
        $this->findIdsByGivenCriteria = $doctrineListBuilderReflectionClass->getMethod('findIdsByGivenCriteria');
        $this->findIdsByGivenCriteria->setAccessible(true);
    }

    public function testSetFields(): void
    {
        $this->doctrineListBuilder->setSelectFields(
            [
                new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName),
                new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName),
            ]
        );

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.desc AS desc_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetFieldsWithStandardFieldDescriptor(): void
    {
        $this->doctrineListBuilder->setSelectFields(
            [
                new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName),
                new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName),
                new FieldDescriptor('test', 'test_alias', self::$entityName),
            ]
        );

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.desc AS desc_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.test AS test_alias')->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testIdSelect(): void
    {
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->select(self::$entityNameAlias . '.id AS id')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testPreselectWithNoJoins(): void
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
                        'anotherEntityName.translations',
                        null,
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER
                    ),
                ]
            )
        );

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        // no joins should be made
        $this->queryBuilder->leftJoin(Argument::cetera())->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();
        $this->queryBuilder->innerJoin(Argument::cetera())->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();

        $this->findIdsByGivenCriteria->invoke($this->doctrineListBuilder);
    }

    public function testPreselectWithJoinsBecauseOfInnerJoin(): void
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
                        'anotherEntityName.translations',
                        null,
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER
                    ),
                ]
            )
        );

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->innerJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            ''
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->innerJoin(
            'anotherEntityName.translations',
            'anotherEntityName',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            ''
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->findIdsByGivenCriteria->invoke($this->doctrineListBuilder);
    }

    public function testPreselectWithConditions(): void
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
                    'anotherEntityName.translations'),
            ]
        );

        $this->doctrineListBuilder->addSelectField($fieldDescriptor);
        $this->doctrineListBuilder->where($fieldDescriptor, 'test');

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->andWhere(Argument::containingString('anotherEntityName.name = :name_alias'))->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('name_alias'), 'test')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            ''
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            'anotherEntityName.translations',
            'anotherEntityName',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            ''
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->findIdsByGivenCriteria->invoke($this->doctrineListBuilder);
    }

    public function testAddField(): void
    {
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName));
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName));

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.desc AS desc_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testAddStandardField(): void
    {
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName));
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName));
        $this->doctrineListBuilder->addSelectField(new FieldDescriptor('test', 'test_alias', self::$entityName));

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.desc AS desc_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.test AS test_alias')->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testAddFieldWithJoin(): void
    {
        $this->doctrineListBuilder->addSelectField(
            new DoctrineFieldDescriptor(
                'desc', 'desc_alias', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName, self::$entityNameAlias . '.translations'
                    ),
                ]
            )
        );

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$translationEntityNameAlias . '.desc AS desc_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            'WITH',
            ''
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testAssignParametersForExecute(): void
    {
        $this->queryBuilder->getDQL()->willReturn('SELECT * FROM table WHERE locale = :locale AND parent = :parent');

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->setParameter('locale', 'de');
        $this->doctrineListBuilder->setParameter('parent', '7');
        $this->doctrineListBuilder->setParameter('webspace', 'sulu');

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->setParameter('locale', 'de')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('parent', '7')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('webspace', Argument::any())->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testAssignParametersForCount(): void
    {
        $this->queryBuilder->getDQL()->willReturn('SELECT * FROM table WHERE locale = :locale AND parent = :parent');

        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('name', 'alias', self::$entityName));
        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldNotBeCalled();

        $this->doctrineListBuilder->setParameter('locale', 'de');
        $this->doctrineListBuilder->setParameter('parent', '7');
        $this->doctrineListBuilder->setParameter('webspace', 'sulu');

        $this->queryBuilder->setParameter('locale', 'de')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('parent', '7')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('webspace', Argument::any())->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();

        $this->doctrineListBuilder->count();
    }

    public function testSearchFieldWithJoin(): void
    {
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor(
                'name', 'name', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName, self::$entityNameAlias . '.translations'
                    ),
                ]
            )
        );

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        // join is only needed in the preselect query, not in the main query. therefore it should be added a one time
        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            'WITH',
            ''
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);

        $this->doctrineListBuilder->execute();
    }

    public function testWhereWithJoin(): void
    {
        $this->doctrineListBuilder->where(
            new DoctrineFieldDescriptor(
                'name', 'name', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName, self::$entityNameAlias . '.translations'
                    ),
                ]
            ),
            'test-name'
        );

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        // join is only needed in the preselect query, not in the main query. therefore it should be added a one time
        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            'WITH',
            ''
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->andWhere(Argument::containingString('.name = :name'))->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('name'), 'test-name')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSelectFieldWithJoin(): void
    {
        $this->doctrineListBuilder->addSelectField(
            new DoctrineFieldDescriptor(
                'name', 'name', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName, self::$entityNameAlias . '.translations'
                    ),
                ]
            )
        );

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        // join is only needed in the main query, not in the preselect query. therefore it should be added a one time
        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            'WITH',
            ''
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_ExampleTranslation.name AS name')->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);

        $this->doctrineListBuilder->execute();
    }

    public function testSortFieldWithJoin(): void
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

        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        // join should be added two times: one time in the preselect query and one time in the main query
        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            'WITH',
            ''
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(2);

        $this->queryBuilder->getDQLPart('select')->willReturn([]);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        // will be called for preselect query
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_ExampleTranslation.desc AS desc_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        // will be called for result (should not be displayed)
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_ExampleTranslation.desc AS HIDDEN desc_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addOrderBy('desc_alias', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSearch(): void
    {
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('desc', 'desc', self::$translationEntityName)
        );
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('name', 'name', self::$entityName)
        );
        $this->doctrineListBuilder->search('value');

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->andWhere(
            '(LOWER(' . self::$translationEntityNameAlias . '.desc) LIKE LOWER(:search) OR LOWER(' . self::$entityNameAlias . '.name) LIKE LOWER(:search))'
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('search', '%value%')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSearchWithPlaceholder(): void
    {
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('desc', 'desc', self::$translationEntityName)
        );
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('name', 'name', self::$entityName)
        );

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->search('val*e');

        $this->queryBuilder->andWhere(
            '(LOWER(' . self::$translationEntityNameAlias . '.desc) LIKE LOWER(:search) OR LOWER(' . self::$entityNameAlias . '.name) LIKE LOWER(:search))'
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('search', '%val%e%')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testFilter(): void
    {
        $filterType = $this->prophesize(FilterTypeInterface::class);
        $this->filterTypeRegistry->getFilterType('text')->willReturn($filterType->reveal());

        $nameFieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $nameMetadata = new SinglePropertyMetadata('name');
        $nameMetadata->setFilterType('text');
        $nameFieldDescriptor->setMetadata($nameMetadata);

        $this->doctrineListBuilder->setFieldDescriptors([
            'name' => $nameFieldDescriptor,
        ]);
        $this->doctrineListBuilder->filter(['name' => 'value']);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $filterType->filter($this->doctrineListBuilder, $nameFieldDescriptor, 'value')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSearchWithoutSearchFields(): void
    {
        $this->expectException(InvalidSearchException::class);

        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldNotBeCalled();

        $this->doctrineListBuilder->search('value');
        $this->doctrineListBuilder->execute();
    }

    public function testSort(): void
    {
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));

        $this->queryBuilder->getDQLPart('select')->willReturn([]);

        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        // will be called for result (should not be displayed)
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS HIDDEN desc')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        // will be called for id query
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addOrderBy('desc', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSortWithExistingSelect(): void
    {
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));

        $this->queryBuilder->getDQLPart('select')->willReturn([new Select('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')]);

        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        // will NOT be called for result (should not be displayed)
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS HIDDEN desc')->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();
        // will be called for id query
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addOrderBy('desc', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    /**
     * Test if multiple calls to sort with same field descriptor will lead to multiple order by calls.
     */
    public function testSortWithMultipleSort(): void
    {
        $this->queryBuilder->getDQLPart('select')->willReturn([new Select('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')]);

        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));

        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);
        $this->queryBuilder->addOrderBy('desc', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(2);

        $this->doctrineListBuilder->execute();
    }

    /**
     * Test if sort is correnctly overwritten, when field descriptor is provided multiple times.
     */
    public function testChangeSortOrder(): void
    {
        $this->queryBuilder->getDQLPart('select')->willReturn([new Select('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')]);

        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName), 'ASC');
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName), 'DESC');

        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);
        $this->queryBuilder->addOrderBy('desc', 'DESC')->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(2);

        $this->doctrineListBuilder->execute();
    }

    public function testSortWithoutDefault(): void
    {
        // when no sort is applied, results should be orderd by id by default
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSortConcat(): void
    {
        $select = 'CONCAT(Sulu_Bundle_CoreBundle_Entity_Example.name, CONCAT(\' \', Sulu_Bundle_CoreBundle_Entity_Example.desc)) AS name_desc';

        $this->doctrineListBuilder->sort(new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineFieldDescriptor('name', 'name', self::$entityName),
                new DoctrineFieldDescriptor('desc', 'desc', self::$entityName),
            ],
            'name_desc'
        ));

        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect($select)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $selectExpression = $this->prophesize(Select::class);
        $selectExpression->getParts()->willReturn([$select]);
        $this->queryBuilder->getDQLPart('select')->willReturn($this->queryBuilder->reveal())->willReturn([$selectExpression->reveal()]);

        $this->queryBuilder->addOrderBy('name_desc', 'ASC')
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalledTimes(2);

        $this->doctrineListBuilder->execute();
    }

    public function testLimit(): void
    {
        $this->doctrineListBuilder->limit(5);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setMaxResults(5)->shouldBeCalled()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled()->willReturn($this->queryBuilder->reveal());

        $this->doctrineListBuilder->execute();
    }

    public function testSetIds(): void
    {
        $this->doctrineListBuilder->setIds([11, 22]);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('id'), [11, 22])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id IN (:id')
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetIdsEmpty(): void
    {
        $this->doctrineListBuilder->setIds([]);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString(' IS NULL')
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetIdsNull(): void
    {
        $this->doctrineListBuilder->setIds(null);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id IN (:id')
        )->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetExcludedIds(): void
    {
        $this->doctrineListBuilder->setExcludedIds([55, 99]);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('id'), [55, 99])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('NOT(Sulu_Bundle_CoreBundle_Entity_Example.id IN (:id')
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetExcludedIdsEmpty(): void
    {
        $this->doctrineListBuilder->setExcludedIds([]);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('NOT(Sulu_Bundle_CoreBundle_Entity_Example.id IN (:id')
        )->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetExcludedIdsNull(): void
    {
        $this->doctrineListBuilder->setExcludedIds(null);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('NOT(Sulu_Bundle_CoreBundle_Entity_Example.id IN (:id')
        )->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testCount(): void
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

        $this->queryBuilder->andWhere(Argument::cetera())->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addOrderBy(Argument::cetera())->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();
        $this->queryBuilder->leftJoin(Argument::cetera())->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);
        $this->queryBuilder->setParameter(Argument::cetera())->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);
        $this->queryBuilder->setMaxResults(Argument::cetera())->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();
        $this->queryBuilder->setFirstResult(Argument::cetera())->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();

        $this->doctrineListBuilder->count();
    }

    public function testSetWhereWithSameName(): void
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
            'desc_id' => new DoctrineFieldDescriptor('id', 'desc_id', self::$entityName),
        ];

        $filter = [
            'title_id' => 3,
            'desc_id' => 1,
        ];

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS title_id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS desc_id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title'), 3)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('desc'), 1)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id = :title_id')
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id = :desc_id')
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value);
        }

        $this->assertCount(2, $this->readObjectAttribute($this->doctrineListBuilder, 'expressions'));
        $expressions = $this->readObjectAttribute($this->doctrineListBuilder, 'expressions');
        $this->assertEquals(3, $expressions[0]->getValue());
        $this->assertEquals(1, $expressions[1]->getValue());

        $this->assertCount(2, $this->readObjectAttribute($this->doctrineListBuilder, 'expressions'));
        $this->assertEquals('title_id', $expressions[0]->getFieldName());
        $this->assertEquals('desc_id', $expressions[1]->getFieldName());
        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereWithNull(): void
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
        ];

        $filter = [
            'title_id' => null,
        ];

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS title_id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), Argument::any())->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value);
        }

        $this->queryBuilder->andWhere('(Sulu_Bundle_CoreBundle_Entity_Example.id IS NULL)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereWithNotNull(): void
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
        ];

        $filter = [
            'title_id' => null,
        ];

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS title_id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), Argument::any())->shouldNotBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value, ListBuilderInterface::WHERE_COMPARATOR_UNEQUAL);
        }

        $this->queryBuilder->andWhere('(Sulu_Bundle_CoreBundle_Entity_Example.id IS NOT NULL)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereNot(): void
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
            'desc_id' => new DoctrineFieldDescriptor('id', 'desc_id', self::$entityName),
        ];

        $filter = [
            'title_id' => 3,
            'desc_id' => 1,
        ];

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS title_id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS desc_id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), 3)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('desc_id'), 1)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id != :title_id')
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id != :desc_id')
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value, ListBuilderInterface::WHERE_COMPARATOR_UNEQUAL);
        }

        $this->assertCount(2, $this->readObjectAttribute($this->doctrineListBuilder, 'expressions'));
        $expressions = $this->readObjectAttribute($this->doctrineListBuilder, 'expressions');
        $this->assertEquals(3, $expressions[0]->getValue());
        $this->assertEquals(1, $expressions[1]->getValue());

        $this->assertCount(2, $this->readObjectAttribute($this->doctrineListBuilder, 'expressions'));
        $this->assertEquals('title_id', $expressions[0]->getFieldName());
        $this->assertEquals('desc_id', $expressions[1]->getFieldName());
        $this->doctrineListBuilder->execute();
    }

    public function testSetIn(): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('id', 'title_id', self::$entityName);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS title_id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), [1, 2])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id IN (:title_id')
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->addSelectField($fieldDescriptor);
        $this->doctrineListBuilder->in($fieldDescriptor, [1, 2]);

        $this->doctrineListBuilder->execute();
    }

    public function testJoinMethods(): void
    {
        $fieldDescriptors = [
            'id1' => new DoctrineFieldDescriptor(
                '',
                '',
                '',
                '',
                [
                    'a' => new DoctrineJoinDescriptor('a', 'a.test', '', DoctrineJoinDescriptor::JOIN_METHOD_LEFT),
                ]
            ),
            'id2' => new DoctrineFieldDescriptor(
                '',
                '',
                '',
                '',
                [
                    'b' => new DoctrineJoinDescriptor('b', 'b.test', '', DoctrineJoinDescriptor::JOIN_METHOD_INNER),
                ]
            ),
        ];

        $this->doctrineListBuilder->setSelectFields($fieldDescriptors);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect('. AS ')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        // not necessary for id join
        $this->queryBuilder->leftJoin('a.test', 'a', 'WITH', '')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        // called when select ids and for selecting data
        $this->queryBuilder->innerJoin('b.test', 'b', 'WITH', '')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testJoinWithoutFieldName(): void
    {
        $fieldDescriptors = [
            'name' => new DoctrineFieldDescriptor(
                'name',
                'name',
                self::$entityName,
                '',
                [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        null,
                        'alias.id = translation.id'
                    ),
                ]
            ),
        ];

        $this->doctrineListBuilder->setSelectFields($fieldDescriptors);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            self::$translationEntityName,
            self::$translationEntityNameAlias,
            'WITH',
            'alias.id = translation.id'
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testJoinWithoutFieldNameByGivenEntity(): void
    {
        $fieldDescriptors = [
            'name' => new DoctrineFieldDescriptor(
                'name',
                'name',
                self::$entityName,
                '',
                [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        self::$translationEntityName,
                        'alias.id = translation.id'
                    ),
                ]
            ),
        ];

        $this->doctrineListBuilder->setSelectFields($fieldDescriptors);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            self::$translationEntityName,
            self::$translationEntityNameAlias,
            'WITH',
            'alias.id = translation.id'
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testJoinConditions(): void
    {
        $fieldDescriptors = [
            'id1' => new DoctrineFieldDescriptor(
                '',
                '',
                '',
                '',
                [
                    self::$entityName . '1' => new DoctrineJoinDescriptor(
                        self::$entityName . '1',
                        null,
                        'field1 = value1',
                        DoctrineJoinDescriptor::JOIN_METHOD_LEFT
                    ),
                ]
            ),
            'id2' => new DoctrineFieldDescriptor(
                '',
                '',
                '',
                '',
                [
                    self::$entityName . '2' => new DoctrineJoinDescriptor(
                        self::$entityName . '2',
                        null,
                        'field2 = value2',
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER,
                        DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_ON
                    ),
                ]
            ),
        ];
        $this->doctrineListBuilder->setSelectFields($fieldDescriptors);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('. AS ')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->leftJoin(
            self::$entityName . '1',
            self::$entityNameAlias . '1',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            'field1 = value1'
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->innerJoin(
            self::$entityName . '2',
            self::$entityNameAlias . '2',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_ON,
            'field2 = value2'
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->doctrineListBuilder->execute();
    }

    public function testGroupBy(): void
    {
        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldNotBeCalled();

        $queryBuilder1 = $this->prophesize(QueryBuilder::class);
        $query1 = $this->prophesize(AbstractQuery::class);
        $queryBuilder2 = $this->prophesize(QueryBuilder::class);
        $query2 = $this->prophesize(AbstractQuery::class);
        $queryBuilder3 = $this->prophesize(QueryBuilder::class);
        $query3 = $this->prophesize(AbstractQuery::class);
        $this->entityManager->createQueryBuilder()->willReturn(
            $queryBuilder1->reveal(),
            $queryBuilder2->reveal(),
            $queryBuilder3->reveal()
        );

        $nameFieldDescriptor = new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName);
        $countFieldDescriptor = new DoctrineCountFieldDescriptor('id', 'count', self::$entityName);

        $query1->getArrayResult()->willReturn([
            [
                'id' => 1,
            ],
            [
                'id' => 2,
            ],
        ]);
        $queryBuilder1->getDQL()->willReturn('');
        $queryBuilder1->getQuery()->willReturn($query1->reveal());
        $queryBuilder1->from(self::$entityName, self::$entityNameAlias)->willReturn($queryBuilder1->reveal());
        $queryBuilder1->setMaxResults(10)->willReturn($queryBuilder1->reveal());
        $queryBuilder1->setFirstResult(0)->willReturn($queryBuilder1->reveal());
        $queryBuilder1->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($queryBuilder1->reveal());
        $queryBuilder1->select(self::$entityNameAlias . '.id AS id')->shouldBeCalled()->willReturn($queryBuilder1->reveal());

        $query2->getArrayResult()->willReturn([
            [
                'id' => 1,
                'name' => 'Test 1',
            ],
            [
                'id' => 2,
                'name' => 'Test 2',
            ],
        ]);
        $queryBuilder2->getDQL()->willReturn('');
        $queryBuilder2->getQuery()->willReturn($query2->reveal());
        $queryBuilder2->from(self::$entityName, self::$entityNameAlias)->willReturn($queryBuilder2->reveal());
        $queryBuilder2->distinct(false)->willReturn($queryBuilder2->reveal());
        $queryBuilder2->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($queryBuilder2->reveal());
        $queryBuilder2->addSelect(self::$entityNameAlias . '.name AS name_alias')->shouldBeCalled()->willReturn($queryBuilder2->reveal());
        $queryBuilder2->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled()->willReturn($queryBuilder2->reveal());
        $queryBuilder2->where(self::$entityNameAlias . '.id IN (:ids)')->shouldBeCalled()->willReturn($queryBuilder2->reveal());
        $queryBuilder2->setParameter('ids', [1, 2])->shouldBeCalled()->willReturn($queryBuilder2->reveal());

        $query3->getArrayResult()->willReturn([
            1 => [
                'id' => 1,
                'count' => 10,
            ],
            2 => [
                'id' => 2,
                'count' => 20,
            ],
        ]);
        $queryBuilder3->getDQL()->willReturn('');
        $queryBuilder3->getQuery()->willReturn($query3->reveal());
        $queryBuilder3->from(self::$entityName, self::$entityNameAlias)->willReturn($queryBuilder3->reveal());
        $queryBuilder3->distinct(false)->willReturn($queryBuilder3->reveal());
        $queryBuilder3->addSelect('COUNT(' . self::$entityNameAlias . '.id) AS count')->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->where(self::$entityNameAlias . '.id IN (:ids)')->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->setParameter('ids', [1, 2])->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->addGroupBy(self::$entityNameAlias . '.name')->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->indexBy(self::$entityNameAlias, self::$entityNameAlias . '.id')->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($queryBuilder3->reveal());

        $this->doctrineListBuilder->setSelectFields([
            $nameFieldDescriptor,
            $countFieldDescriptor,
        ]);

        $this->doctrineListBuilder->addGroupBy($nameFieldDescriptor);

        $result = $this->doctrineListBuilder->execute();
        $this->assertSame([
            [
                'id' => 1,
                'name' => 'Test 1',
                'count' => 10,
            ],
            [
                'id' => 2,
                'name' => 'Test 2',
                'count' => 20,
            ],
        ], $result);
    }

    public function testBetween(): void
    {
        $nameFieldDescriptor = new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.name AS name_alias')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.name BETWEEN :name_alias')
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);
        $this->queryBuilder->setParameter(Argument::containingString('name_alias'), 0)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('name_alias'), 1)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->setSelectFields(
            [
                $nameFieldDescriptor,
            ]
        );

        $this->doctrineListBuilder->between($nameFieldDescriptor, [0, 1]);

        $this->doctrineListBuilder->execute();
    }

    public function testDistinct(): void
    {
        $this->doctrineListBuilder->distinct(true);

        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->distinct(true)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testNoDistinct(): void
    {
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testIdField(): void
    {
        $idField = $this->prophesize(DoctrineFieldDescriptorInterface::class);
        $idField->getSelect()->willReturn('example.id');
        $idField->getName()->willReturn('id');

        $this->doctrineListBuilder->setIdField($idField->reveal());

        $this->queryBuilder->addOrderBy('example.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->select('example.id AS id')->shouldBeCalled()->willReturn($this->queryBuilder->reveal())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->addSelect('example.id AS id')->shouldBeCalled()->willReturn($this->queryBuilder->reveal())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->where('example.id IN (:ids)')->shouldBeCalled()->willReturn($this->queryBuilder->reveal())->willReturn($this->queryBuilder->reveal());

        $this->doctrineListBuilder->execute();
    }

    public function testIdFieldChanged(): void
    {
        $idField = $this->prophesize(DoctrineFieldDescriptorInterface::class);
        $idField->getSelect()->willReturn('example.uuid');
        $idField->getName()->willReturn('other');

        $this->doctrineListBuilder->setIdField($idField->reveal());
        $this->query->getArrayResult()->willReturn([
            [
                'other' => 1,
            ],
            [
                'other' => 2,
            ],
            [
                'other' => 3,
            ],
        ]);

        $this->queryBuilder->addOrderBy('example.uuid', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addSelect('example.uuid AS other')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->where('example.uuid IN (:ids)')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());

        $this->doctrineListBuilder->execute();
    }

    public function testNoIdField(): void
    {
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder
            ->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS id')
            ->shouldBeCalled()
            ->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder
            ->where('Sulu_Bundle_CoreBundle_Entity_Example.id IN (:ids)')
            ->shouldBeCalled()
            ->willReturn($this->queryBuilder->reveal());

        $this->doctrineListBuilder->execute();
    }

    public function testSetPermissionCheck(): void
    {
        $user = $this->prophesize(User::class);
        $role = $this->prophesize(Role::class);
        $role->getId()->willReturn(1);
        $role->getSystem()->willReturn('Sulu');
        $user->getRoleObjects()->willReturn([$role->reveal()]);

        $this->doctrineListBuilder->setPermissionCheck($user->reveal(), PermissionTypes::VIEW);

        $accessQueryBuilder = $this->prophesize(QueryBuilder::class);
        $noRestrictionsQueryBuilder = $this->prophesize(QueryBuilder::class);
        $expr = $this->prophesize(Expr::class);

        $this->entityManager->createQueryBuilder()->willReturn(
            $this->queryBuilder->reveal(),
            $accessQueryBuilder->reveal(),
            $noRestrictionsQueryBuilder->reveal(),
            $this->queryBuilder->reveal()
        );

        // New EXISTS-based query structure
        $accessQueryBuilder->select('1')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->from(AccessControl::class, 'acl')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->innerJoin('acl.role', 'role')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->where('role.id IN (:roleIds)')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('BIT_AND(acl.permissions, :permission) = :permission')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('acl.permissions IS NOT NULL')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('acl.entityClass = :entityClass')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('acl.entityId = ' . self::$entityNameAlias . '.id')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->getDQL()
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->shouldBeCalled();

        // Mock for NOT EXISTS subquery (checking if any AccessControl records exist)
        $noRestrictionsQueryBuilder->select('1')
            ->shouldBeCalled()
            ->willReturn($noRestrictionsQueryBuilder->reveal());

        $noRestrictionsQueryBuilder->from(AccessControl::class, 'acl_check')
            ->shouldBeCalled()
            ->willReturn($noRestrictionsQueryBuilder->reveal());

        $noRestrictionsQueryBuilder->where('acl_check.entityId = ' . self::$entityNameAlias . '.id')
            ->willReturn($noRestrictionsQueryBuilder->reveal())
            ->shouldBeCalled();

        $noRestrictionsQueryBuilder->andWhere('acl_check.entityClass = :entityClass')
            ->willReturn($noRestrictionsQueryBuilder->reveal())
            ->shouldBeCalled();

        $noRestrictionsQueryBuilder->getDQL()
            ->willReturn('SELECT 1 FROM AccessControl acl_check ...')
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('roleIds', [1])
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('permission', 64)
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('entityClass', self::$entityName)
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->expr()
            ->willReturn($expr->reveal())
            ->shouldBeCalled();

        $expr->exists('SELECT 1 FROM AccessControl acl_check ...')
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->shouldBeCalled();

        $expr->not('EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->willReturn('NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->shouldBeCalled();

        $expr->exists('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->shouldBeCalled();

        $expr->orX('NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...)', 'EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->willReturn('(NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...) OR EXISTS (SELECT 1 FROM AccessControl acl ...))')
            ->shouldBeCalled();

        $this->queryBuilder->andWhere('(NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...) OR EXISTS (SELECT 1 FROM AccessControl acl ...))')
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetPermissionCheckIntIdentifier(): void
    {
        $user = $this->prophesize(User::class);
        $role = $this->prophesize(Role::class);
        $role->getId()->willReturn(1);
        $role->getSystem()->willReturn('Sulu');
        $user->getRoleObjects()->willReturn([$role->reveal()]);

        $this->doctrineListBuilder->setPermissionCheck($user->reveal(), PermissionTypes::VIEW);

        $accessQueryBuilder = $this->prophesize(QueryBuilder::class);
        $noRestrictionsQueryBuilder = $this->prophesize(QueryBuilder::class);
        $expr = $this->prophesize(Expr::class);

        $this->entityManager->createQueryBuilder()->willReturn(
            $this->queryBuilder->reveal(),
            $accessQueryBuilder->reveal(),
            $noRestrictionsQueryBuilder->reveal(),
            $this->queryBuilder->reveal()
        );

        $this->classMetadata->getTypeOfField('id')
            ->willReturn('integer')
            ->shouldBeCalled();

        // New EXISTS-based query structure
        $accessQueryBuilder->select('1')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->from(AccessControl::class, 'acl')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->innerJoin('acl.role', 'role')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->where('role.id IN (:roleIds)')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('BIT_AND(acl.permissions, :permission) = :permission')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('acl.permissions IS NOT NULL')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('acl.entityClass = :entityClass')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        // For integer ID, use entityIdInteger
        $accessQueryBuilder->andWhere('acl.entityIdInteger = ' . self::$entityNameAlias . '.id')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->getDQL()
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->shouldBeCalled();

        // Mock for NOT EXISTS subquery (checking if any AccessControl records exist)
        $noRestrictionsQueryBuilder->select('1')
            ->shouldBeCalled()
            ->willReturn($noRestrictionsQueryBuilder->reveal());

        $noRestrictionsQueryBuilder->from(AccessControl::class, 'acl_check')
            ->shouldBeCalled()
            ->willReturn($noRestrictionsQueryBuilder->reveal());

        // For integer ID, use entityIdInteger in noRestrictionsQueryBuilder too
        $noRestrictionsQueryBuilder->where('acl_check.entityIdInteger = ' . self::$entityNameAlias . '.id')
            ->willReturn($noRestrictionsQueryBuilder->reveal())
            ->shouldBeCalled();

        $noRestrictionsQueryBuilder->andWhere('acl_check.entityClass = :entityClass')
            ->willReturn($noRestrictionsQueryBuilder->reveal())
            ->shouldBeCalled();

        $noRestrictionsQueryBuilder->getDQL()
            ->willReturn('SELECT 1 FROM AccessControl acl_check ...')
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('roleIds', [1])
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('permission', 64)
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('entityClass', self::$entityName)
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->expr()
            ->willReturn($expr->reveal())
            ->shouldBeCalled();

        $expr->exists('SELECT 1 FROM AccessControl acl_check ...')
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->shouldBeCalled();

        $expr->not('EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->willReturn('NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->shouldBeCalled();

        $expr->exists('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->shouldBeCalled();

        $expr->orX('NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...)', 'EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->willReturn('(NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...) OR EXISTS (SELECT 1 FROM AccessControl acl ...))')
            ->shouldBeCalled();

        $this->queryBuilder->andWhere('(NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...) OR EXISTS (SELECT 1 FROM AccessControl acl ...))')
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetPermissionCheckWithSecuredEntityName(): void
    {
        $user = $this->prophesize(User::class);
        $role = $this->prophesize(Role::class);
        $role->getId()->willReturn(1);
        $role->getSystem()->willReturn('Sulu');
        $user->getRoleObjects()->willReturn([$role->reveal()]);

        $this->doctrineListBuilder->setPermissionCheck($user->reveal(), PermissionTypes::VIEW, \stdClass::class);

        $accessQueryBuilder = $this->prophesize(QueryBuilder::class);
        $noRestrictionsQueryBuilder = $this->prophesize(QueryBuilder::class);
        $expr = $this->prophesize(Expr::class);

        $this->entityManager->createQueryBuilder()->willReturn(
            $this->queryBuilder->reveal(),
            $accessQueryBuilder->reveal(),
            $noRestrictionsQueryBuilder->reveal(),
            $this->queryBuilder->reveal()
        );

        $accessQueryBuilder->select('1')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->from(AccessControl::class, 'acl')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->innerJoin('acl.role', 'role')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->where('role.id IN (:roleIds)')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('BIT_AND(acl.permissions, :permission) = :permission')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('acl.permissions IS NOT NULL')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('acl.entityClass = :entityClass')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('acl.entityId = stdClass.id')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->getDQL()
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->shouldBeCalled();

        // Mock for NOT EXISTS subquery (checking if any AccessControl records exist)
        $noRestrictionsQueryBuilder->select('1')
            ->shouldBeCalled()
            ->willReturn($noRestrictionsQueryBuilder->reveal());

        $noRestrictionsQueryBuilder->from(AccessControl::class, 'acl_check')
            ->shouldBeCalled()
            ->willReturn($noRestrictionsQueryBuilder->reveal());

        // For stdClass tests, use 'stdClass' as the entity alias
        $noRestrictionsQueryBuilder->where('acl_check.entityId = stdClass.id')
            ->willReturn($noRestrictionsQueryBuilder->reveal())
            ->shouldBeCalled();

        $noRestrictionsQueryBuilder->andWhere('acl_check.entityClass = :entityClass')
            ->willReturn($noRestrictionsQueryBuilder->reveal())
            ->shouldBeCalled();

        $noRestrictionsQueryBuilder->getDQL()
            ->willReturn('SELECT 1 FROM AccessControl acl_check ...')
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('roleIds', [1])
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('permission', 64)
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('entityClass', \stdClass::class)
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->expr()
            ->willReturn($expr->reveal())
            ->shouldBeCalled();

        $expr->exists('SELECT 1 FROM AccessControl acl_check ...')
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->shouldBeCalled();

        $expr->not('EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->willReturn('NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->shouldBeCalled();

        $expr->exists('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->shouldBeCalled();

        $expr->orX('NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...)', 'EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->willReturn('(NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...) OR EXISTS (SELECT 1 FROM AccessControl acl ...))')
            ->shouldBeCalled();

        $this->queryBuilder->andWhere('(NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...) OR EXISTS (SELECT 1 FROM AccessControl acl ...))')
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetPermissionCheckWithSecuredEntityNameAndAdditionalJoins(): void
    {
        $user = $this->prophesize(User::class);
        $role = $this->prophesize(Role::class);
        $role->getId()->willReturn(1);
        $role->getSystem()->willReturn('Sulu');
        $user->getRoleObjects()->willReturn([$role->reveal()]);

        $joinFieldDescriptor = $this->prophesize(DoctrineJoinDescriptor::class);
        $joinFieldDescriptor->getEntityName()->willReturn('MyTest');
        $joinFieldDescriptor->getJoin()->willReturn('stdClass.myTest');
        $joinFieldDescriptor->getJoinMethod()->willReturn(DoctrineJoinDescriptor::JOIN_METHOD_LEFT);
        $joinFieldDescriptor->getJoinConditionMethod()->willReturn(DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_ON);
        $joinFieldDescriptor->getJoinCondition()->willReturn('stdClass.id = MyTest.id');

        $permissionCheckField = $this->prophesize(DoctrineFieldDescriptor::class);
        $permissionCheckField->getEntityName()->willReturn('MyTest');
        $permissionCheckField->getJoins()->willReturn(['MyTest' => $joinFieldDescriptor->reveal()]);

        $this->doctrineListBuilder->setPermissionCheck($user->reveal(), PermissionTypes::VIEW, \stdClass::class);
        $this->doctrineListBuilder->addPermissionCheckField($permissionCheckField->reveal());

        $accessQueryBuilder = $this->prophesize(QueryBuilder::class);
        $noRestrictionsQueryBuilder = $this->prophesize(QueryBuilder::class);
        $expr = $this->prophesize(Expr::class);

        $this->entityManager->createQueryBuilder()->willReturn(
            $this->queryBuilder->reveal(),
            $accessQueryBuilder->reveal(),
            $noRestrictionsQueryBuilder->reveal(),
            $this->queryBuilder->reveal()
        );

        $accessQueryBuilder->select('1')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->from(AccessControl::class, 'acl')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->innerJoin('acl.role', 'role')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->where('role.id IN (:roleIds)')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('BIT_AND(acl.permissions, :permission) = :permission')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('acl.permissions IS NOT NULL')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('acl.entityClass = :entityClass')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->andWhere('acl.entityId = stdClass.id')
            ->willReturn($accessQueryBuilder->reveal())
            ->shouldBeCalled();

        $accessQueryBuilder->getDQL()
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->shouldBeCalled();

        // Mock for NOT EXISTS subquery (checking if any AccessControl records exist)
        $noRestrictionsQueryBuilder->select('1')
            ->shouldBeCalled()
            ->willReturn($noRestrictionsQueryBuilder->reveal());

        $noRestrictionsQueryBuilder->from(AccessControl::class, 'acl_check')
            ->shouldBeCalled()
            ->willReturn($noRestrictionsQueryBuilder->reveal());

        // For stdClass tests, use 'stdClass' as the entity alias
        $noRestrictionsQueryBuilder->where('acl_check.entityId = stdClass.id')
            ->willReturn($noRestrictionsQueryBuilder->reveal())
            ->shouldBeCalled();

        $noRestrictionsQueryBuilder->andWhere('acl_check.entityClass = :entityClass')
            ->willReturn($noRestrictionsQueryBuilder->reveal())
            ->shouldBeCalled();

        $noRestrictionsQueryBuilder->getDQL()
            ->willReturn('SELECT 1 FROM AccessControl acl_check ...')
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('roleIds', [1])
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('permission', 64)
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('entityClass', \stdClass::class)
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->expr()
            ->willReturn($expr->reveal())
            ->shouldBeCalled();

        $expr->exists('SELECT 1 FROM AccessControl acl_check ...')
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->shouldBeCalled();

        $expr->not('EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->willReturn('NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...)')
            ->shouldBeCalled();

        $expr->exists('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->willReturn('EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->shouldBeCalled();

        $expr->orX('NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...)', 'EXISTS (SELECT 1 FROM AccessControl acl ...)')
            ->willReturn('(NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...) OR EXISTS (SELECT 1 FROM AccessControl acl ...))')
            ->shouldBeCalled();

        $this->queryBuilder->andWhere('(NOT EXISTS (SELECT 1 FROM AccessControl acl_check ...) OR EXISTS (SELECT 1 FROM AccessControl acl ...))')
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            'stdClass.myTest',
            'MyTest',
            'ON',
            'stdClass.id = MyTest.id'
        )
            ->willReturn($this->queryBuilder->reveal())
            ->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testCreateIsNullExpression(): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('test', 'test', self::$entityName);
        $this->queryBuilder->addOrderBy(Argument::cetera())
            ->willReturn($this->queryBuilder->reveal())
            ->shouldNotBeCalled();

        $expression = $this->doctrineListBuilder->createIsNullExpression($fieldDescriptor);

        $this->assertInstanceOf(DoctrineIsNullExpression::class, $expression);
        $this->assertEquals('test', $expression->getFieldName());
    }

    public function testCreateIsNotNullExpression(): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('test', 'test', self::$entityName);
        $this->queryBuilder->addOrderBy(Argument::cetera())
            ->willReturn($this->queryBuilder->reveal())
            ->shouldNotBeCalled();

        $expression = $this->doctrineListBuilder->createIsNotNullExpression($fieldDescriptor);

        $this->assertInstanceOf(DoctrineIsNotNullExpression::class, $expression);
        $this->assertEquals('test', $expression->getFieldName());
    }

    public function testPaginationWithJoinsAppliesDistinct(): void
    {
        // Test the fix for #8467: DISTINCT should be applied when filtering by joined fields
        $fieldDescriptor = new DoctrineFieldDescriptor(
            'name',
            'name',
            self::$translationEntityName,
            'translation',
            [
                self::$translationEntityName => new DoctrineJoinDescriptor(
                    self::$translationEntityName,
                    self::$entityNameAlias . '.translations'
                ),
            ]
        );

        $this->doctrineListBuilder->where($fieldDescriptor, 'test-value');

        // The ID subquery should call distinct(true) because JOINs are present
        $this->queryBuilder->distinct(true)->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);
        // The final query should call distinct(false) (default behavior)
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(Argument::containingString('.name = :name'))->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('name'), 'test-value')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            'WITH',
            ''
        )->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testPaginationWithoutJoinsNoDistinct(): void
    {
        // Test that DISTINCT is NOT applied when no JOINs are present (no performance overhead)
        $fieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $this->doctrineListBuilder->where($fieldDescriptor, 'test-value');

        // distinct(true) should NOT be called for ID subquery
        $this->queryBuilder->distinct(true)->willReturn($this->queryBuilder->reveal())->shouldNotBeCalled();
        // Only distinct(false) should be called for the final query
        $this->queryBuilder->distinct(false)->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(1);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(Argument::containingString('.name = :name'))->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('name'), 'test-value')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testExplicitDistinctAppliedToIdSubquery(): void
    {
        // Test that explicit distinct(true) is applied to both ID subquery and final query
        $this->doctrineListBuilder->distinct(true);

        // distinct(true) should be called twice: once for ID subquery, once for final query
        $this->queryBuilder->distinct(true)->willReturn($this->queryBuilder->reveal())->shouldBeCalledTimes(2);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->where(self::$entityNameAlias . '.id IN (:ids)')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testCountWithJoinsUsesDistinct(): void
    {
        // Test the fix for #8467: COUNT(DISTINCT id) should be used when JOINs are present
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor(
                'desc',
                'desc',
                self::$translationEntityName,
                'translation',
                [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        self::$entityName . '.translations'
                    ),
                ]
            )
        );
        $this->doctrineListBuilder->search('value');

        // Verify COUNT(DISTINCT ...) is used in the select
        $this->queryBuilder->select(Argument::containingString('COUNT(DISTINCT'))->shouldBeCalled()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->leftJoin(Argument::cetera())->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->andWhere(Argument::cetera())->willReturn($this->queryBuilder->reveal())->shouldBeCalled();
        $this->queryBuilder->setParameter('search', '%value%')->willReturn($this->queryBuilder->reveal())->shouldBeCalled();

        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldNotBeCalled();

        $this->doctrineListBuilder->count();
    }

    public function testCountWithoutJoinsNoDistinct(): void
    {
        // Test that regular COUNT(id) is used when no JOINs are present
        $this->doctrineListBuilder->addSelectField(
            new DoctrineFieldDescriptor('name', 'name', self::$entityName)
        );

        // Verify COUNT(...) without DISTINCT is used
        $this->queryBuilder->select(Argument::that(function($arg) {
            return \is_string($arg) && \str_contains($arg, 'COUNT(') && !\str_contains($arg, 'DISTINCT');
        }))->shouldBeCalled()->willReturn($this->queryBuilder->reveal());

        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldNotBeCalled();

        $this->doctrineListBuilder->count();
    }
}
