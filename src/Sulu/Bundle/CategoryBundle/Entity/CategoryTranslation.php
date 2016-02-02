<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Entity;

/**
 * CategoryTranslation.
 */
class CategoryTranslation
{
    /**
     * @var string
     */
    private $translation;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Sulu\Bundle\CategoryBundle\Entity\Category
     */
    private $category;

    /**
     * Set translation.
     *
     * @param string $translation
     *
     * @return CategoryTranslation
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * Get translation.
     *
     * @return string
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return CategoryTranslation
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set category.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $category
     *
     * @return CategoryTranslation
     */
    public function setCategory(\Sulu\Bundle\CategoryBundle\Entity\Category $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return \Sulu\Bundle\CategoryBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }
}
