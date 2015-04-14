<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Serializer;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Structure\Factory\StructureFactory;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\DocumentManager\Document\UnknownDocument;

/**
 * Handle serializeation and deserialization of the PageBridge
 */
class PageBridgeHandler implements SubscribingHandlerInterface
{
    private $structureFactory;
    private $inspector;
    private $propertyFactory;

    public function __construct(
        DocumentInspector $inspector,
        LegacyPropertyFactory $propertyFactory,
        StructureFactory $structureFactory
    )
    {
        $this->structureFactory = $structureFactory;
        $this->inspector = $inspector;
        $this->propertyFactory = $propertyFactory;
    }

    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => PageBridge::class,
                'method' => 'doSerialize',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => PageBridge::class,
                'method' => 'doDeserialize',
            ),
        );
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param NodeInterface $nodeInterface
     * @param array $type
     * @param Context $context
     */
    public function doSerialize(
        JsonSerializationVisitor $visitor,
        PageBridge $bridge,
        array $type,
        Context $context
    ) {
        $refl = new \ReflectionClass(PageBridge::class);
        $documentProperty = $refl->getProperty('document');
        $structureProperty = $refl->getProperty('structure');
        $documentProperty->setAccessible(true);
        $structureProperty->setAccessible(true);

        $document = $documentProperty->getValue($bridge);
        $structure = $structureProperty->getValue($bridge);

        $context->accept(array(
            'document' => $document,
            'structure' => $structure->name
        ));
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param NodeInterface $nodeInterface
     * @param array $type
     * @param Context $context
     */
    public function doDeserialize(
        JsonDeserializationVisitor $visitor,
        array $data,
        array $type,
        Context $context
    ) {

        $document = $context->accept($data['document'], array('name' => PageDocument::class));
        $structure = $this->structureFactory->getStructure('page', $data['structure']);

        $bridge = new PageBridge($structure, $this->inspector, $this->propertyFactory, $document);

        // filthy hack to set the Visitor::$result to null and force the
        // serializer to return the Bridge and not the Document
        $visitor->setNavigator($context->getNavigator());

        return $bridge;
    }
}
