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

@\trigger_error(
    \sprintf(
        'The "%s" class is deprecated since Sulu 2.3. Use the respective event class directly instead.',
        TagEvents::class
    ),
    \E_USER_DEPRECATED
);

/**
 * @deprecated
 */
final class TagEvents
{
    /**
     * The tag.delete event is thrown when a Tag is deleted.
     * The event listener receives a TagDeleteEvent instance.
     *
     * @var string
     */
    const TAG_DELETE = 'sulu.tag.delete';

    /**
     * The tag.merge event is thrown when a Tag gets merged into another one.
     * The event listener receives a TagMergeEvent instance.
     */
    const TAG_MERGE = 'sulu.tag.merge';
}
