<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\Keyword;

/**
 * Manages keyword for categories.
 */
interface KeywordManagerInterface
{
    const FORCE_OVERWRITE = 'overwrite';
    const FORCE_DETACH = 'detach';
    const FORCE_MERGE = 'merge';

    /**
     * Add given keyword to the category.
     *
     * @param Keyword $keyword
     * @param Category $category
     * @param string $force
     *
     * @return Keyword
     */
    public function save(Keyword $keyword, Category $category, $force = null);

    /**
     * Removes keyword from given category.
     *
     * @param Keyword $keyword
     * @param Category $category
     *
     * @return bool true if keyword is deleted completely from the database otherwise only from the category
     */
    public function delete(Keyword $keyword, Category $category);
}
