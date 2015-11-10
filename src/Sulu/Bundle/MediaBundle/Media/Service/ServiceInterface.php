<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Service;

use Sulu\Bundle\MediaBundle\Api\Media;

/**
 * Defines an media event subscriber.
 */
interface ServiceInterface
{
    /**
     * Send add external media request.
     *
     * @param Media[] $media
     *
     * @return bool
     */
    public function add(array $media);

    /**
     * Send update external media request.
     *
     * @param Media[] $media
     *
     * @return bool
     */
    public function update(array $media);

    /**
     * Send delete external media request.
     *
     * @param Media[] $media
     *
     * @return bool
     */
    public function delete(array $media);
}
