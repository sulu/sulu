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
use League\Flysystem\Dropbox\DropboxAdapter;

class DropBoxResolver implements ResolverInterface
{
    /**
     * @return string
     */
    public function getUrl(AdapterInterface $adapter, $fileName)
    {
        if ($adapter instanceof DropboxAdapter) {
            $path = $adapter->applyPathPrefix($fileName);

            try {
                return $adapter->getClient()->createShareableLink(
                    $path
                );
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

}