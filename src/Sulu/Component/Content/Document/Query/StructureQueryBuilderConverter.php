<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Query;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder as BaseQueryBuilder;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Query\Builder as Sulu;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentStrategyInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\Query\QueryBuilderConverter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Extends the Sulu Document Manager query builder converter which in turn extends
 * the PHPCR-ODM query builder.
 *
 * The purpose of this class is to convert a query builder instance into a
 * PHPCR query object.
 */
class StructureQueryBuilderConverter extends QueryBuilderConverter
{
    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetaFactory;

    /**
     * @var array
     */
    private $structureMap = [];

    public function __construct(
        SessionInterface $session,
        EventDispatcherInterface $eventDispatcher,
        MetadataFactoryInterface $metadataFactory,
        PropertyEncoder $encoder,
        DocumentStrategyInterface $strategy,
        StructureMetadataFactoryInterface $structureMetaFactory
    ) {
        parent::__construct($session, $eventDispatcher, $metadataFactory, $encoder, $strategy);
        $this->structureMetaFactory = $structureMetaFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(BaseQueryBuilder $queryBuilder)
    {
        // Sulu uses its own subclass of the PHPCR-ODM query builder.
        if (!$queryBuilder instanceof QueryBuilder) {
            throw new \BadMethodCallException(sprintf(
                'StructureQueryBuilderConverter must be passed an instance of the Sulu\Component\Content\Document\Query\QueryBuilder, got "%s"',
                get_class($queryBuilder)
            ));
        }

        $this->structureMap = $queryBuilder->getStructureMap();

        return parent::getQuery($queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(AbstractNode $node)
    {
        $isSulu = false;

        if (in_array($node->getName(), [
            'Where',
            'WhereOr',
            'WhereAnd',
            'ConstraintAndX',
            'ConstraintOrX',
            'ConstraintNot',
            'ConstraintComparison',
        ])) {
            $isSulu = true;
        }

        // we need to call specific methods
        $methodName = sprintf('walk%s%s',
            $isSulu ? 'Sulu' : '',
            $node->getName()
        );

        if (!method_exists($this, $methodName)) {
            throw new \InvalidArgumentException(sprintf(
                'Do not know how to walk node of type "%s"',
                $node->getName()
            ));
        }

        return $this->$methodName($node);
    }

    /**
     * Call the parent method with the Doctrine version of the Sulu node.
     *
     * @see Doctrine\ODM\PHPCR\Query\Builder\ConverterBase::where
     */
    public function walkSuluWhere(Sulu\Where $where)
    {
        return parent::walkWhere($where->getDoctrineInstance());
    }

    /**
     * Call the parent method with the Doctrine version of the Sulu node.
     *
     * @see Doctrine\ODM\PHPCR\Query\Builder\ConverterBase::whereOr
     */
    public function walkSuluWhereOr(Sulu\WhereOr $where)
    {
        return parent::walkWhereOr($where->getDoctrineInstance());
    }

    /**
     * Call the parent method with the Doctrine version of the Sulu node.
     *
     * @see Doctrine\ODM\PHPCR\Query\Builder\ConverterBase::whereAnd
     */
    public function walkSuluWhereAnd(Sulu\WhereAnd $where)
    {
        return parent::walkWhereAnd($where->getDoctrineInstance());
    }

    /**
     * Call the parent method with the Doctrine version of the Sulu node.
     *
     * @see Doctrine\ODM\PHPCR\Query\Builder\ConverterBase::ConstraintAndx
     */
    public function walkSuluConstraintAndX(Sulu\ConstraintAndX $node)
    {
        return parent::walkConstraintAndX($node->getDoctrineInstance());
    }

    /**
     * Call the parent method with the Doctrine version of the Sulu node.
     *
     * @see Doctrine\ODM\PHPCR\Query\Builder\ConverterBase::walkConstraintOrX
     */
    public function walkSuluConstraintOrX(Sulu\ConstraintOrX $node)
    {
        return parent::walkConstraintOrX($node->getDoctrineInstance());
    }

    /**
     * Call the parent method with the Doctrine version of the Sulu node.
     *
     * @see Doctrine\ODM\PHPCR\Query\Builder\ConverterBase::walkConstraintNot
     */
    public function walkSuluConstraintNot(Sulu\ConstraintNot $node)
    {
        return parent::walkConstraintNot($node->getDoctrineInstance());
    }

    /**
     * Call the parent method with the Doctrine version of the Sulu node.
     *
     * @see Doctrine\ODM\PHPCR\Query\Builder\ConverterBase::walkConstraintComparison
     */
    public function walkSuluConstraintComparison(Sulu\ConstraintComparison $node)
    {
        return parent::walkConstraintComparison($node->getDoctrineInstance());
    }

    /**
     * Walk the Sulu StructureField contsraint.
     *
     * @param Sulu\OperandDynamicStructureField $node
     *
     * @return PHPCR\Query\QOM\PropertyValue
     */
    public function walkOperandDynamicStructureField(Sulu\OperandDynamicStructureField $node)
    {
        $alias = $node->getAlias();

        if (!isset($this->structureMap[$alias])) {
            throw new \InvalidArgumentException(sprintf(
                'No structure has been registered for document alias "%s". Use ->useStructure(\'%s\', \'structure_name\') to register a structure name. ' .
                'Registered document aliases: "%s"',
                $alias,
                $alias,
                implode('", "', array_keys($this->documentMetadata))
            ));
        }

        if (!isset($this->documentMetadata[$alias])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown document alias "%s". Known document aliases "%s"',
                $alias,
                implode('", "', array_keys($this->documentMetadata))
            ));
        }
        $metadata = $this->documentMetadata[$alias];

        $structure = $this->structureMetaFactory->getStructureMetadata($metadata->getAlias(), $this->structureMap[$alias]);
        $propertyMetadata = $structure->getProperty($node->getStructureField());
        $phpcrName = $this->encoder->fromProperty($propertyMetadata, $this->locale);

        $phpcrOperand = $this->qomf->propertyValue(
            $alias,
            $phpcrName
        );

        return $phpcrOperand;
    }
}
