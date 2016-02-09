<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

class InvalidHashException extends RestException
{
    public function __construct($entity, $id)
    {
        parent::__construct(
            sprintf(
                'The given hash for the entity of type "%s" with the id "%s" does not match the current hash.'
                . ' The entity has probably been edited in the mean time.',
                $entity,
                $id
            ),
            1102
        );
    }
}
