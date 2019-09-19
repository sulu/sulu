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
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\SmartContent\Rest\ItemCollectionRepresentation;

/**
 * @internal
 *
 * This handler workaround some problems with serialize Representation in specific groups
 */
class CollectionRepresentationHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => CollectionRepresentation::class,
                'method' => 'serializeForJson',
            ],
        ];
    }

    /**
     * FIXME This should be refractored so the annoations are used and no manually mapping here is needed.
     *
     * Currently the manually mapping is needed when the developer set a SerializationGroup
     * It will ignore the Annoations in the Representation classes and the response will always
     * be an empty json object {}. This Serializer Handler does currently workaround this issue
     * but should in future be optimized to keep the Annotations of the Representation class
     * in content.
     */
    public function serializeForJson(
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

        if ($representation instanceof ItemCollectionRepresentation) {
            $data['total'] = $representation->getTotal();
            $data['datasource'] = $representation->getDatasource();
        }

        $data['_embedded'] = [
            $representation->getRel() => $representation->getData(),
        ];

        $context->getNavigator()->accept($data, null, $context);

        return $context;
    }
}
