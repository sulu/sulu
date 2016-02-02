<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Event;

use Sulu\Bundle\TagBundle\Entity\Tag;
use Symfony\Component\EventDispatcher\Event;

/**
 * An object of this class is thrown along with the tag.delete event.
 */
class TagDeleteEvent extends Event
{
    /**
     * @var Tag
     */
    protected $tag;

    /**
     * @param Tag $tag The deleted tag
     */
    public function __construct(Tag $tag)
    {
        $this->tag = $tag;
    }

    /**
     * Returns the deleted tag.
     *
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }
}
