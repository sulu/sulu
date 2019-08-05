<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

interface FormMetadataLoaderInterface extends CacheWarmerInterface
{
    public function getMetadata(string $key, string $locale);
}
