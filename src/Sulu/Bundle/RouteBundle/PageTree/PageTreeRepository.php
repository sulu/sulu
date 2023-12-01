<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\PageTree;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\RouteBundle\Content\Type\PageTreeRouteContentType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Route\Document\Behavior\RoutableBehavior;

/**
 * Update the route of documents synchronously.
 */
class PageTreeRepository implements PageTreeUpdaterInterface, PageTreeMoverInterface
{
    public const ROUTE_PROPERTY = 'routePath';

    public const TAG_NAME = 'sulu_route.route_path';

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var StructureMetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var PropertyEncoder
     */
    protected $propertyEncoder;

    /**
     * @var DocumentInspector
     */
    protected $documentInspector;

    public function __construct(
        DocumentManagerInterface $documentManager,
        StructureMetadataFactoryInterface $metadataFactory,
        PropertyEncoder $propertyEncoder,
        DocumentInspector $documentInspector
    ) {
        $this->documentManager = $documentManager;
        $this->metadataFactory = $metadataFactory;
        $this->propertyEncoder = $propertyEncoder;
        $this->documentInspector = $documentInspector;
    }

    public function update(BasePageDocument $parentDocument)
    {
        $documents = $this->findLinkedDocuments('page', $parentDocument->getUuid(), $parentDocument->getLocale());
        foreach ($documents as $document) {
            $this->updateDocument($document, $parentDocument);
        }
    }

    public function move($source, BasePageDocument $parentDocument)
    {
        $documents = $this->findLinkedDocuments('page-path', $source, $parentDocument->getLocale());
        foreach ($documents as $document) {
            $this->updateDocument($document, $parentDocument);
        }
    }

    /**
     * Find documents linked to the given page.
     *
     * @return RoutableBehavior[]
     */
    private function findLinkedDocuments(string $field, string $value, string $locale): iterable
    {
        $where = [];

        foreach ($this->metadataFactory->getStructureTypes() as $structureType) {
            foreach ($this->metadataFactory->getStructures($structureType) as $metadata) {
                $property = $this->getRoutePathPropertyByMetadata($metadata);

                if (null === $property || PageTreeRouteContentType::NAME !== $property->getType()) {
                    continue;
                }

                $where[] = \sprintf(
                    '([%s] = "%s" AND [%s-%s] = "%s")',
                    $this->propertyEncoder->localizedSystemName('template', $locale),
                    $metadata->getName(),
                    $this->propertyEncoder->localizedContentName($property->getName(), $locale),
                    $field,
                    $value
                );
            }
        }

        if (0 === \count($where)) {
            return [];
        }

        $query = $this->documentManager->createQuery(
            \sprintf(
                'SELECT * FROM [nt:unstructured] WHERE (%s)',
                \implode(' OR ', $where)
            ),
            $locale
        );

        return $query->execute();
    }

    /**
     * Update route of given document.
     */
    private function updateDocument(RoutableBehavior $document, BasePageDocument $parentDocument): void
    {
        $locale = $parentDocument->getLocale();
        $resourceSegment = $parentDocument->getResourceSegment();

        $property = $this->getRoutePathProperty($document);
        $propertyName = $this->propertyEncoder->localizedContentName($property->getName(), $locale);

        $node = $this->documentInspector->getNode($document);
        $node->setProperty($propertyName . '-page', $parentDocument->getUuid());
        $node->setProperty($propertyName . '-page-path', $resourceSegment);

        /** @var string|null $suffix */
        $suffix = $node->getPropertyValueWithDefault($propertyName . '-suffix', null);
        if ($suffix) {
            $path = \rtrim($resourceSegment, '/') . '/' . \ltrim($suffix, '/');
            $node->setProperty($propertyName, $path);
            $document->setRoutePath($path);

            $routeProperty = $document->getStructure()->getProperty($property->getName());
            $routeValue = $routeProperty->getValue();
            $routeValue['page']['path'] = $resourceSegment;
            $routeValue['path'] = $path;
            $routeProperty->setValue($routeValue);
        }

        $workflowStage = $document->getWorkflowStage();

        $this->documentManager->persist($document, $locale);
        if (WorkflowStage::PUBLISHED === $workflowStage) {
            $this->documentManager->publish($document, $locale);
        }
    }

    /**
     * Returns encoded "routePath" property.
     */
    private function getRoutePathProperty(RoutableBehavior $document): PropertyMetadata
    {
        $metadata = $this->documentInspector->getStructureMetadata($document);

        if ($metadata->hasTag(self::TAG_NAME)) {
            return $metadata->getPropertyByTagName(self::TAG_NAME);
        }

        return $metadata->getProperty(self::ROUTE_PROPERTY);
    }

    /**
     * Returns encoded "routePath" property by metadata.
     */
    private function getRoutePathPropertyByMetadata(StructureMetadata $metadata): ?PropertyMetadata
    {
        if ($metadata->hasTag(self::TAG_NAME)) {
            return $metadata->getPropertyByTagName(self::TAG_NAME);
        }

        if (!$metadata->hasProperty(self::ROUTE_PROPERTY)) {
            return null;
        }

        return $metadata->getProperty(self::ROUTE_PROPERTY);
    }
}
