<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Exception;

/**
 * An instance of this exception signals that no category is assigned to a specific id.
 */
class CategoryIdNotFoundException extends \Exception
{
    /**
     * @var mixed
     */
    private $categoryId;

    /**
     * CategoryIdNotFoundException constructor.
     *
     * @param mixed $categoryId
     */
    public function __construct($categoryId)
    {
        parent::__construct(sprintf('The category with the id "%s" does not exist.', $categoryId));

        $this->categoryId = $categoryId;
    }

    /**
     * @return mixed Id which is not associated with any category
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }
}
