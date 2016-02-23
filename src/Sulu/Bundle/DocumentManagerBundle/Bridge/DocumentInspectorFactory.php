<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Bridge;

use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentInspectorFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerContext;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Creates document inspector instances for a given document manager context.
 */
class DocumentInspectorFactory implements DocumentInspectorFactoryInterface
{
    /**
     * @var NamespaceRegistry
     */
    private $namespaceRegistry;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureFactory;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var PathSegmentRegistry
     */
    private $pathSegmentRegistry;

    /**
     * @var DocumentInspector[]
     */
    private $inspector = [];

    public function __construct(
        PathSegmentRegistry $pathSegmentRegistry,
        NamespaceRegistry $namespaceRegistry,
        MetadataFactoryInterface $metadataFactory,
        StructureMetadataFactoryInterface $structureFactory,
        PropertyEncoder $encoder,
        WebspaceManagerInterface $webspaceManager
    ) {
        $this->pathSegmentRegistry = $pathSegmentRegistry;
        $this->namespaceRegistry = $namespaceRegistry;
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
        $this->encoder = $encoder;
        $this->webspaceManager = $webspaceManager;
        $this->pathSegmentRegistry = $pathSegmentRegistry;
    }

    /**
     * Create a new instance of the inspector.
     *
     * {@inheritdoc}
     */
    public function getInspector(DocumentManagerContext $context)
    {
        $hash = spl_object_hash($context);

        if (isset($this->inspector[$hash])) {
            return $this->inspector[$hash];
        }

        $inspector = new DocumentInspector(
            $context->getRegistry(),
            $this->pathSegmentRegistry,
            $this->namespaceRegistry,
            $context->getProxyFactory(),
            $this->metadataFactory,
            $this->structureFactory,
            $this->encoder,
            $this->webspaceManager
        );

        $this->inspector[$hash] = $inspector;

        return $inspector;
    }
}
