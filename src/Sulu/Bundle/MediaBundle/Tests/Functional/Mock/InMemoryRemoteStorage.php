<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Mock;

use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemV3Storage;

class InMemoryRemoteStorage extends FlysystemV3Storage
{
    public function getType(array $storageOptions): string
    {
        return self::TYPE_REMOTE;
    }
}
