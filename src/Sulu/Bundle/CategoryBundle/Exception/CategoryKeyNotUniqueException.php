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
 * An instance of this exception signals that a specific key is already assigned to another category.
 */
class CategoryKeyNotUniqueException extends \Exception
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
        parent::__construct(sprintf('The category key "%s" is already in use.', $categoryKey));

        $this->categoryKey = $categoryKey;
    }

    /**
     * @return mixed Key which is already used
     */
    public function getCategoryKey()
    {
        return $this->categoryKey;
    }
}
