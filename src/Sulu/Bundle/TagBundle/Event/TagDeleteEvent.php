<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Event;

use Sulu\Bundle\TagBundle\Domain\Event\TagRemovedEvent;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * An object of this class is thrown along with the tag.delete event.
 *
 * @deprecated
 */
class TagDeleteEvent extends Event
{
    /**
     * @var TagInterface
     */
    protected $tag;

    /**
     * @param TagInterface $tag The deleted tag
     */
    public function __construct(TagInterface $tag)
    {
        @trigger_deprecation(
            'sulu/sulu',
            '2.3',
            'The "%s" class is deprecated. Use the "%s" class instead.',
            TagDeleteEvent::class,
            TagRemovedEvent::class
        );

        $this->tag = $tag;
    }

    /**
     * Returns the deleted tag.
     *
     * @return TagInterface
     */
    public function getTag()
    {
        return $this->tag;
    }
}
