<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;

/**
 * Handle serialization and deserialization of the PageBridge.
 */
class PageBridgeHandler implements SubscribingHandlerInterface
{
    /**
     * @var StructureMetadataFactory
     */
    private $structureFactory;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var LegacyPropertyFactory
     */
    private $propertyFactory;

    public function __construct(
        DocumentInspector $inspector,
        LegacyPropertyFactory $propertyFactory,
        StructureMetadataFactory $structureFactory
    ) {
        $this->structureFactory = $structureFactory;
        $this->inspector = $inspector;
        $this->propertyFactory = $propertyFactory;
    }

    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => PageBridge::class,
                'method' => 'doSerialize',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => PageBridge::class,
                'method' => 'doDeserialize',
            ],
        ];
    }

    /**
     * @param VisitorInterface $visitor
     * @param PageBridge $bridge
     * @param array $type
     * @param Context $context
     */
    public function doSerialize(
        VisitorInterface $visitor,
        PageBridge $bridge,
        array $type,
        Context $context
    ) {
        $context->accept(
            [
                'document' => $bridge->getDocument(),
                'documentClass' => get_class($bridge->getDocument()),
                'structure' => $bridge->getStructure()->getName(),
            ]
        );
    }

    /**
     * @param VisitorInterface $visitor
     * @param array $data
     * @param array $type
     * @param Context $context
     *
     * @return PageBridge
     */
    public function doDeserialize(
        VisitorInterface $visitor,
        array $data,
        array $type,
        Context $context
    ) {
        $document = $context->accept($data['document'], ['name' => $data['documentClass']]);
        $structure = $this->structureFactory->getStructureMetadata('page', $data['structure']);
        $bridge = new PageBridge($structure, $this->inspector, $this->propertyFactory, $document);

        // filthy hack to set the Visitor::$result to null and force the
        // serializer to return the Bridge and not the Document
        $visitor->setNavigator($context->getNavigator());

        return $bridge;
    }
}
