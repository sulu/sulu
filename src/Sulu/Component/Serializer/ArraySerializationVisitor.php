<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Serializer;

use JMS\Serializer\GenericSerializationVisitor;

/**
 * Enables serialization to an array with the JMSSerializer.
 */
class ArraySerializationVisitor extends GenericSerializationVisitor
{
    /**
     * Returns the visited data as array.
     *
     * @return array
     */
    public function getResult()
    {
        return $this->getRoot();
    }
}
