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

@\trigger_error(
    \sprintf(
        'The "%s" class is deprecated since Sulu 2.3. Use the "%s" class instead.',
        CategoryDeleteEvent::class,
        CategoryRemovedEvent::class
    ),
    \E_USER_DEPRECATED
);

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
