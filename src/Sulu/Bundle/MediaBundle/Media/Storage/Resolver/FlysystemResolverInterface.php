<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage\Resolver;

use League\Flysystem\AdapterInterface;
use League\Flysystem\FilesystemInterface;

interface FlysystemResolverInterface
{
    /**
     * @param FilesystemInterface $fileSystem
     * @param string $fileName
     *
     * @return null|string
     */
    public function getUrl(FilesystemInterface $fileSystem, $fileName);

    /**
     * @param AdapterInterface $adapter
     * @param string $fileName
     *
     * @return null|string
     */
    public function getUrlFromAdapter(AdapterInterface $adapter, $fileName);
}