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

interface ArraySerializerInterface
{
    public function serialize($data, ?SerializationContext $context = null): array;
}
