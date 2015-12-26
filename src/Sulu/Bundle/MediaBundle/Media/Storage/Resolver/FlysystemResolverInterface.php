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
use Sulu\Bundle\MediaBundle\Media\Storage\Resolver\Flysystem\ResolverInterface;

/**
 * Interface to define the methods to extend flysystem adapters.
 */
interface FlysystemResolverInterface
{
    /**
     * @param string $class
     * @param ResolverInterface $resolver
     */
    public function add(ResolverInterface $resolver, $class);

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
