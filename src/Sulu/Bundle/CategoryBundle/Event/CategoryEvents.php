<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Event;

@trigger_deprecation(
    'sulu/sulu',
    '2.3',
    'The "%s" class is deprecated. Use the respective event class directly instead.',
    CategoryEvents::class
);

/**
 * @deprecated
 */
final class CategoryEvents
{
    /**
     * The category.delete event is thrown after a category got deleted.
     * The event listener receives a CategoryDeleteEvent instance.
     *
     * @var string
     */
    public const CATEGORY_DELETE = 'sulu.category.delete';
}
