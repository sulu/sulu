<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Event;

use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An object of this class is thrown along with the category.delete event.
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
