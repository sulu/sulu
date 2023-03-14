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

use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryRemovedEvent;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * An object of this class is thrown along with the category.delete event.
 *
 * @deprecated
 */
class CategoryDeleteEvent extends Event
{
    /**
     * @var CategoryInterface
     */
    protected $category;

    /**
     * @param CategoryInterface $category The deleted category
     */
    public function __construct(CategoryInterface $category)
    {
        @trigger_deprecation(
            'sulu/sulu',
            '2.3',
            'The "%s" class is deprecated. Use the "%s" class instead.',
            __CLASS__,
            CategoryRemovedEvent::class
        );

        $this->category = $category;
    }

    /**
     * Returns the deleted category.
     *
     * @return CategoryInterface
     */
    public function getCategory()
    {
        return $this->category;
    }
}
