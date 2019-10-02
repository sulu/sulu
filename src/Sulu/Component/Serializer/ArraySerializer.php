<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Serializer;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;

class ArraySerializer implements ArraySerializerInterface
{
    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function serialize($data, ?SerializationContext $context = null): array
    {
        if (!$context) {
            $context = SerializationContext::create();
        }

        $context->setAttribute('array_serializer', true);

        return $this->serializer->toArray($data, $context);
    }
}
