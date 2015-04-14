<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Serializer\Handler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use Sulu\Component\Content\Document\Property\PropertyContainer;

/**
 * Handle serializeation and deserialization of document content
 */
class PropertyContainerHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => PropertyContainer::class,
                'method' => 'doSerialize',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => PropertyContainer::class,
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
        PropertyContainer $container,
        array $type,
        Context $context
    ) {
        $array = $container->getArrayCopy();

        return $context->accept(array(
            'typeMap' => $this->getContentTypeMap($array),
            'content' => $array,
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
        $container = new PropertyContainer();

        if (!isset($data['content'])) {
            return $container;
        }

        $typeMap = $data['typeMap'];
        $content = $data['content'];

        foreach ($content as $key => $value) {
            $type = $typeMap[$key];
            $deserialized = $context->accept(
                $value,
                array(
                    'name' => $type[0],
                    'params' => isset($type[1]) ? array(array('name' => $type[1])) : array()
                )
            );

            $container->getProperty($key)->setValue($deserialized);
        }

        return $container;
    }

    /**
     * Recursively map the type of each content
     *
     * @param array $content
     * @return array
     */
    private function getContentTypeMap($content)
    {
        $typeMap = array();
        foreach ($content as $key => $value) {
            if (is_array($value) || $value instanceof \Traversable) {
                if (!count($value)) {
                    continue;
                }

                $typeMap[$key] = array('array', $this->getType(reset($value)));
                continue;
            }

            $typeMap[$key] = array($this->getType($value), null);
        }

        return $typeMap;
    }

    private function getType($value)
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }

}
