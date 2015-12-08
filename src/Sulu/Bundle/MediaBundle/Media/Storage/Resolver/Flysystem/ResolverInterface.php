<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage\Resolver\Flysystem;

use League\Flysystem\AdapterInterface;

/**
 * Defines resolver interface to extend flysystem adapters.
 */
interface ResolverInterface
{
    /**
     * @return string
     */
    public function getUrl(AdapterInterface $adapter, $fileName);
}
