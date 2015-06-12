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
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MediaApiEvent
 * To Call Events with Media Api Objects
 */
class ApiMediaEvent extends Event implements ApiMediaEventInterface
{
    /**
     * @var Media
     */
    protected $media;

    /**
     * @param Media $media
     */
    public function __construct(
        Media $media
    ) {
        $this->media = $media;
    }

    /**
     * {@inheritdoc}
     */
    public function getMedia()
    {
        return $this->media;
    }
}
