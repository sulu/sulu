<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

class CollectionNotFoundException extends MediaException
{
    /**
     * @param string $id
     */
    public function __construct($id)
    {
        parent::__construct('Collection with the ID ' . $id . ' was not found', self::EXCEPTION_CODE_COLLECTION_NOT_FOUND);
    }
}
