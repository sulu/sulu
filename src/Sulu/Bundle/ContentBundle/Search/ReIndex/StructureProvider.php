<?php

namespace Sulu\Bundle\ContentBundle\Search\ReIndex;

use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Massive\Bundle\SearchBundle\Search\ReIndex\ReIndexProviderInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;

class StructureProvider implements ReIndexProviderInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var StructureMetadataFactory
     */
    private $structureFactory;

    public function __construct(
        DocumentManagerInterface $documentManager,
        MetadataFactoryInterface $metadataFactory,
        StructureMetadataFactoryInterface $structureFactory
    )
    {
        $this->documentManager = $documentManager;
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function provide($classFqn, $offset, $maxResults)
    {
        $query = $this->getQuery($classFqn);
        $query->setFirstResult($offset);
        $query->setMaxResults($maxResults);

        return $query->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getCount($classFqn)
    {
        $query = $this->getQuery($classFqn);
        return count($query->execute());
    }

    /**
     * {@inheritdoc}
     */
    public function getClassFqns()
    {
        $classFqns = array();
        foreach ($this->metadataFactory->getAllMetadata() as $metadata) {
            if (!$this->structureFactory->hasStructuresFor($metadata->getAlias())) {
                continue;
            }

            $classFqns[] = $metadata->getClass();
        }

        return $classFqns;
    }

    private function getQuery($classFqn)
    {
        $metadata = $this->metadataFactory->getMetadataForClass($classFqn);

        // TODO: Use the document manager query builder.
        return $this->documentManager->createQuery(sprintf(
            'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "%s"',
            $metadata->getPhpcrType()
        ));
    }
}
