<?php

namespace Sulu\Bundle\ContentBundle\Search\ReIndex;

use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Massive\Bundle\SearchBundle\Search\ReIndex\LocalizedReIndexProviderInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentInspector;

class StructureProvider implements LocalizedReIndexProviderInterface
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

    /**
     * @var DocumentInspector
     */
    private $inspector;

    public function __construct(
        DocumentManagerInterface $documentManager,
        MetadataFactoryInterface $metadataFactory,
        StructureMetadataFactoryInterface $structureFactory,
        DocumentInspector $inspector
    )
    {
        $this->documentManager = $documentManager;
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
        $this->inspector = $inspector;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalesForObject($object)
    {
        return $this->inspector->getLocales($object);
    }

    /**
     * {@inheritdoc}
     */
    public function translateObject($object, $locale)
    {
        return $this->documentManager->find($this->inspector->getUuid($object), $locale);
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
