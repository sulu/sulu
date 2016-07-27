<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentInspector;

/**
 * Admin route defaults provider for home and page documents.
 *
 * Will be used to find the controller for this document types.
 */
class PageRouteDefaultsProvider implements RouteDefaultsProviderInterface
{
    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @param StructureMetadataFactoryInterface $structureMetadataFactory
     * @param DocumentInspector $inspector
     * @param StructureManagerInterface $structureManager
     */
    public function __construct(
        StructureMetadataFactoryInterface $structureMetadataFactory,
        DocumentInspector $inspector,
        StructureManagerInterface $structureManager
    ) {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->inspector = $inspector;
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritdoc}
     *
     * This function wont work for website mode.
     * To enable this the object would have to loaded in case the argument $object is null.
     */
    public function getByEntity($entityClass, $id, $locale, $object = null)
    {
        $metadata = $this->structureMetadataFactory->getStructureMetadata('page', $object->getStructureType());

        return [
            '_controller' => $metadata->controller,
            'view' => $metadata->view,
            'object' => $object,
            'structure' => $this->documentToStructure($object),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isPublished($entityClass, $id, $locale)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entityClass)
    {
        return HomeDocument::class === $entityClass || PageDocument::class === $entityClass;
    }

    /**
     * Return a structure bridge corresponding to the given document.
     *
     * @param BasePageDocument $document
     *
     * @return PageBridge
     */
    protected function documentToStructure(BasePageDocument $document)
    {
        $structure = $this->inspector->getStructureMetadata($document);
        $documentAlias = $this->inspector->getMetadata($document)->getAlias();

        $structureBridge = $this->structureManager->wrapStructure($documentAlias, $structure);
        $structureBridge->setDocument($document);

        return $structureBridge;
    }
}
