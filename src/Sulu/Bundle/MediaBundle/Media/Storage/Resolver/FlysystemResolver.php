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
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

class FlysystemResolver implements FlysystemResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function getUrl(FilesystemInterface $fileSystem, $fileName)
    {
        if ($fileSystem instanceof Filesystem) {
            return $this->getUrlFromAdapter($fileSystem->getAdapter(), $fileName);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlFromAdapter(AdapterInterface $adapter, $fileName)
    {
        if ($adapter instanceof DropboxAdapter) {
            return $this->getDropBoxUrl($adapter, $fileName);
        } elseif ($adapter instanceof AwsS3Adapter) {
            return $this->getAwsS3Url($adapter, $fileName);
        }

        return null;
    }

    /**
     * @param $adapter
     * @param $fileName
     *
     * @return null|string
     */
    protected function getAwsS3Url(AwsS3Adapter $adapter, $fileName)
    {
        $bucket = $adapter->getBucket();
        $key = $adapter->applyPathPrefix($fileName);

        return $adapter->getClient()->getObjectUrl($bucket, $key);
    }

    /**
     * @param DropboxAdapter $adapter
     * @param string $fileName
     *
     * @return null|string
     */
    protected function getDropBoxUrl(DropboxAdapter $adapter, $fileName)
    {
        $path = $adapter->applyPathPrefix($fileName);

        try {
            return $adapter->getClient()->createShareableLink(
                $path
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}