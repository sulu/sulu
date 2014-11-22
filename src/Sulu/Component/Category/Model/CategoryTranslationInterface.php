<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Category\Model;

interface CategoryTranslationInterface
{

    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * Set locale
     *
     * @param string $locale
     * @return CategoryTranslationInterface
     */
    public function setLocale($locale);

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set translation
     *
     * @param string $translation
     * @return CategoryTranslationInterface
     */
    public function setTranslation($translation);

    /**
     * Get translation
     *
     * @return string
     */
    public function getTranslation();

    /**
     * Set category
     *
     * @param CategoryInterface $category
     * @return CategoryTranslationInterface
     */
    public function setCategory(CategoryInterface $category);

    /**
     * Get category
     *
     * @return CategoryInterface
     */
    public function getCategory();
}
