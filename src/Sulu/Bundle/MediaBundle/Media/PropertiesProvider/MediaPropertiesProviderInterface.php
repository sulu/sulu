<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\PropertiesProvider;

use Symfony\Component\HttpFoundation\File\File;

interface MediaPropertiesProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function provide(File $file): array;
}
