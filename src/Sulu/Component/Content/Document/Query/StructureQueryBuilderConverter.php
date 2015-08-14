<?php

namespace Sulu\Component\Content\Document\Query;

use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder as BaseQueryBuilder;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\DocumentManager\DocumentStrategyInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\Query\QueryBuilderConverter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StructureQueryBuilderConverter extends QueryBuilderConverter
{
    private $structureMetaFactory;
    private $structureMap = [];

    /**
     * @param SessionInterface $session
     * @param EventDispatcherInterface $eventDispatcher
     * @param MetadataFactoryInterface $metadataFactory
     * @param PropertyEncoder $encoder
     */
    public function __construct(
        SessionInterface $session,
        EventDispatcherInterface $eventDispatcher,
        MetadataFactoryInterface $metadataFactory,
        PropertyEncoder $encoder,
        DocumentStrategyInterface $strategy,
        StructureMetadataFactory $structureMetaFactory
    ) {
        parent::__construct($session, $eventDispatcher, $metadataFactory, $encoder, $strategy);
        $this->structureMetaFactory = $structureMetaFactory;
    }

    public function getQuery(BaseQueryBuilder $queryBuilder)
    {
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
    protected function getPhpcrProperty($alias, $field)
    {
        if (false !== $position = strpos($field, 'structure#')) {
            $propertyName = substr($field, $position + 10);

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
            $propertyMetadata = $structure->getProperty($propertyName);
            $phpcrName = $this->encoder->fromProperty($propertyMetadata, $this->locale);

            return [$alias, $phpcrName];
        }

        return parent::getPhpcrProperty($alias, $field);
    }
}
