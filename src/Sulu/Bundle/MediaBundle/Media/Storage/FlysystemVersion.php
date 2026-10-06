<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage;

use League\Flysystem\FilesystemOperator;

/**
 * @internal
 */
final class FlysystemVersion
{
    public static function isV3(): bool
    {
        return \interface_exists(FilesystemOperator::class);
    }
}
