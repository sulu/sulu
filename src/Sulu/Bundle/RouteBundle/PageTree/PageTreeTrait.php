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
use Sulu\Bundle\RouteBundle\Content\Type\PageTreeRouteContentType;
use Sulu\Bundle\RouteBundle\Document\Subscriber\RoutableSubscriber;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Route\Document\Behavior\RoutableBehavior;

/**
 * Encapsulates function to extract parent page from structure-metadata.
 */
trait PageTreeTrait
{
    /**
     * @return DocumentInspector
     */
    abstract protected function getDocumentInspector();

    /**
     * @return null|string
     */
    protected function getParentPageUuidFromPageTree(RoutableBehavior $document)
    {
        $structureMetadata = $this->getDocumentInspector()->getStructureMetadata($document);

        $propertyMetadata = $this->getRoutePathProperty($structureMetadata);
        if (!$propertyMetadata) {
            return null;
        }

        $property = $document->getStructure()->getProperty($propertyMetadata->getName());
        if (!$property || PageTreeRouteContentType::NAME !== $propertyMetadata->getType()) {
            return null;
        }

        $value = $property->getValue();
        if (!$value || !isset($value['page']) || !isset($value['page']['uuid'])) {
            return null;
        }

        return $value['page']['uuid'];
    }

    /**
     * Returns property-metadata for route-path property.
     *
     * @return null|PropertyMetadata
     */
    private function getRoutePathProperty(StructureMetadata $metadata)
    {
        if ($metadata->hasTag(RoutableSubscriber::TAG_NAME)) {
            return $metadata->getPropertyByTagName(RoutableSubscriber::TAG_NAME);
        }

        if (!$metadata->hasProperty(RoutableSubscriber::ROUTE_PROPERTY)) {
            return null;
        }

        return $metadata->getProperty(RoutableSubscriber::ROUTE_PROPERTY);
    }
}
