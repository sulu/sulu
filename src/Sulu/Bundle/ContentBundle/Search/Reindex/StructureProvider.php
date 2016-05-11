<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\Reindex;

use Massive\Bundle\SearchBundle\Search\Reindex\LocalizedReindexProviderInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;

/**
 * Provides structures for the MassiveSearch reindex process.
 */
class StructureProvider implements LocalizedReindexProviderInterface
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
    ) {
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

        // we do not currently index documents which have permissions.
        $documents = $query->execute();
        $newDocuments = [];
        foreach ($documents as $document) {
            if ($document instanceof SecurityBehavior) {
                if (false === empty($document->getPermissions())) {
                    continue;
                }
            }
            $newDocuments[] = $document;
        }

        return $newDocuments;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanUp($classFqn)
    {
        $this->documentManager->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function getCount($classFqn)
    {
        $query = $this->getQuery($classFqn);

        // note that this count does NOT take into account any documents that
        // may have security (and should thus be excluded) - checking the
        // permissions on each document here would cause significant overhead.
        return count($query->execute());
    }

    /**
     * {@inheritdoc}
     */
    public function getClassFqns()
    {
        $classFqns = [];
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
