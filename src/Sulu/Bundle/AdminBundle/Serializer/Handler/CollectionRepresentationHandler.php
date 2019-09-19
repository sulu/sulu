<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;

class CollectionRepresentationHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => CollectionRepresentation::class,
                'method' => 'serializeToJsonSchema',
            ],
        ];
    }

    public function serializeToJsonSchema(
        JsonSerializationVisitor $visitor,
        CollectionRepresentation $representation,
        array $type,
        Context $context
    ) {
        $data = [];

        if ($representation instanceof PaginatedRepresentation) {
            $data['total'] = $representation->getTotal();
            $data['limit'] = $representation->getLimit();
            $data['page'] = $representation->getPage();
            $data['pages'] = $representation->getPages();
        }

        $data['_embedded'] = [
            $representation->getRel() => $representation->getData(),
        ];

        $context->getNavigator()->accept($data, null, $context);

        return $context;
    }
}
