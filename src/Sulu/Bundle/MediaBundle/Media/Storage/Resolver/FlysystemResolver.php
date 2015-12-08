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
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\Resolver\Flysystem\ResolverInterface;

/**
 * Extends the flysystem adapter to get the url from specific adapters.
 */
class FlysystemResolver implements FlysystemResolverInterface
{
    /**
     * @var ResolverInterface[]
     */
    protected $resolvers = [];

    /**
     * {@inheritdoc}
     */
    public function add(ResolverInterface $resolver, $class)
    {
        $this->resolvers[$class] = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(FilesystemInterface $fileSystem, $fileName)
    {
        if ($fileSystem instanceof Filesystem) {
            return $this->getUrlFromAdapter($fileSystem->getAdapter(), $fileName);
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlFromAdapter(AdapterInterface $adapter, $fileName)
    {
        $class = get_class($adapter);

        if (isset($this->resolvers[$class])) {
            return $this->resolvers[$class]->getUrl($adapter, $fileName);
        }

        return;
    }
}
