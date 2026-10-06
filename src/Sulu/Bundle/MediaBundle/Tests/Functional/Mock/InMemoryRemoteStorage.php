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

use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemStorage;

class InMemoryRemoteStorage extends FlysystemStorage
{
    public const PUBLIC_URL = 'https://cdn.example.com';

    public function getPath(array $storageOptions): string
    {
        return self::PUBLIC_URL . '/' . $this->getFilePath($storageOptions);
    }

    public function getType(array $storageOptions): string
    {
        return self::TYPE_REMOTE;
    }
}
