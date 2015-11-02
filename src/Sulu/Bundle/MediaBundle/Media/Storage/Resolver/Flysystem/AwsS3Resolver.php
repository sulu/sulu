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
use League\Flysystem\AwsS3v3\AwsS3Adapter;

class AwsS3Resolver implements ResolverInterface
{
    /**
     * @return string
     */
    public function getUrl(AdapterInterface $adapter, $fileName)
    {
        if ($adapter instanceof AwsS3Adapter) {
            $bucket = $adapter->getBucket();
            $key = $adapter->applyPathPrefix($fileName);

            return $adapter->getClient()->getObjectUrl($bucket, $key);
        }

        return;
    }
}
