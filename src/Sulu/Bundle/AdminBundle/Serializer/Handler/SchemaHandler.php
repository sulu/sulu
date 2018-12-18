<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Schema;

class SchemaHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => Schema::class,
                'method' => 'serializeToJsonSchema',
            ],
        ];
    }

    public function serializeToJsonSchema(
        JsonSerializationVisitor $visitor,
        Schema $schema,
        array $type,
        Context $context
    ) {
        $jsonSchema = $schema->toJsonSchema();

        return $context->accept(count($jsonSchema) > 0 ? $jsonSchema : null);
    }
}
