<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Search\Reindex;

use Massive\Bundle\SearchBundle\Search\Reindex\LocalizedReindexProviderInterface;
use PHPCR\NodeInterface;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Subscriber\ShadowLocaleSubscriber;
use Sulu\Component\Content\Document\Subscriber\StructureSubscriber;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\Exception\StructureTypeNotFoundException;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\HttpKernel\SuluKernel;

/**
 * Provides structures for the MassiveSearch reindex process.
 */
class StructureProvider implements LocalizedReindexProviderInterface
{
    /**
     * Caches if a structure exists, keyed by "<alias>|<structure type>".
     *
     * @var array<string, bool>
     */
    private array $structureExists = [];

    /**
     * @param string $context
     */
    public function __construct(
        private DocumentManagerInterface $documentManager,
        private MetadataFactoryInterface $metadataFactory,
        private StructureMetadataFactoryInterface $structureFactory,
        private DocumentInspector $inspector,
        private $context,
        private ?PropertyEncoder $encoder = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function getLocalesForObject($object)
    {
        $locales = $this->inspector->getLocales($object);

        if (!$this->encoder || !$object instanceof StructureBehavior) {
            return $locales;
        }

        $node = $this->inspector->getNode($object);
        $alias = $this->inspector->getMetadata($object)->getAlias();

        return \array_values(\array_filter(
            $locales,
            fn ($locale) => $this->hasStructure($object, $node, $alias, $locale),
        ));
    }

    /**
     * Checks if the structure of the given locale exists, so that the document can be indexed in this locale.
     * Documents can reference a template which was removed, or have no template in a locale.
     */
    private function hasStructure(StructureBehavior $object, NodeInterface $node, string $alias, string $locale): bool
    {
        \assert($this->encoder instanceof PropertyEncoder);

        // a shadow locale is loaded with the structure of its base locale (see ShadowLocaleSubscriber)
        $structureLocale = $locale;
        if ($object instanceof ShadowLocaleBehavior
            && $node->getPropertyValueWithDefault(
                $this->encoder->localizedSystemName(ShadowLocaleSubscriber::SHADOW_ENABLED_FIELD, $locale),
                false
            )
        ) {
            $shadowLocale = $node->getPropertyValueWithDefault(
                $this->encoder->localizedSystemName(ShadowLocaleSubscriber::SHADOW_LOCALE_FIELD, $locale),
                null
            );
            $structureLocale = \is_string($shadowLocale) ? $shadowLocale : $locale;
        }

        // same property name as used by the StructureSubscriber
        $propertyName = $object instanceof LocalizedStructureBehavior
            ? $this->encoder->localizedSystemName(StructureSubscriber::STRUCTURE_TYPE_FIELD, $structureLocale)
            : $this->encoder->contentName(StructureSubscriber::STRUCTURE_TYPE_FIELD);
        $structureType = $node->getPropertyValueWithDefault($propertyName, null);
        $structureType = \is_string($structureType) ? $structureType : null;

        $cacheKey = $alias . '|' . ($structureType ?? '');
        if (!\array_key_exists($cacheKey, $this->structureExists)) {
            try {
                $this->structureExists[$cacheKey] = null !== $this->structureFactory->getStructureMetadata($alias, $structureType);
            } catch (StructureTypeNotFoundException) {
                $this->structureExists[$cacheKey] = false;
            }
        }

        if (!$this->structureExists[$cacheKey]) {
            $this->logger?->warning(\sprintf(
                'Structure "%s" of type "%s" not found, skipping search indexing of document "%s" in locale "%s".',
                $structureType,
                $alias,
                $this->inspector->getUuid($object),
                $locale,
            ));
        }

        return $this->structureExists[$cacheKey];
    }

    public function translateObject($object, $locale)
    {
        $document = $this->documentManager->find($this->inspector->getUuid($object), $locale);

        if ($document instanceof WorkflowStageBehavior && SuluKernel::CONTEXT_ADMIN === $this->context) {
            // set the workflowstage to test, so that the document will be indexed in the index for drafting
            // this change must not be persisted
            // is required because of the expression for the index name uses the workflowstage
            $document->setWorkflowStage(WorkflowStage::TEST);
        }

        return $document;
    }

    public function provide($classFqn, $offset, $maxResults)
    {
        $query = $this->getQuery($classFqn);
        $query->setFirstResult($offset);
        $query->setMaxResults($maxResults);

        $documents = $query->execute();
        $newDocuments = [];
        foreach ($documents as $document) {
            if ($document instanceof SecurityBehavior) {
                // we do not currently index documents which have permissions.
                if (false === empty($document->getPermissions())) {
                    continue;
                }
            }

            $newDocuments[] = $document;
        }

        return $newDocuments;
    }

    public function cleanUp($classFqn)
    {
        $this->documentManager->clear();
    }

    public function getCount($classFqn)
    {
        $query = $this->getQuery($classFqn);

        // note that this count does NOT take into account any documents that
        // may have security (and should thus be excluded) - checking the
        // permissions on each document here would cause significant overhead.
        return \count($query->execute());
    }

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
        return $this->documentManager->createQuery(\sprintf(
            'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "%s"',
            $metadata->getPhpcrType()
        ));
    }
}
