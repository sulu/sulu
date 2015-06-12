<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Event;

use Sulu\Bundle\MediaBundle\Api\Media;

/**
 * Interface ApiMediaEventInterface
 * Defines the ApiMediaEvent Structure
 */
interface ApiMediaEventInterface
{
    /**
     * @return Media
     */
    public function getMedia();
}
