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
 * An instance of this exception signals that no category is assigned to a specific key.
 */
class CategoryKeyNotFoundException extends \Exception
{
    /**
     * @var mixed
     */
    private $categoryKey;

    /**
     * CategoryNotFoundException constructor.
     *
     * @param mixed $categoryKey
     */
    public function __construct($categoryKey)
    {
        parent::__construct(sprintf('The category with the key "%s" does not exist.', $categoryKey));

        $this->categoryKey = $categoryKey;
    }

    /**
     * @return mixed Key which is not associated to any category
     */
    public function getCategoryKey()
    {
        return $this->categoryKey;
    }
}
