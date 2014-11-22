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

interface CategoryMetaInterface
{
    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * Set key
     *
     * @param string $key
     * @return CategoryMetaInterface
     */
    public function setKey($key);

    /**
     * Get key
     *
     * @return string
     */
    public function getKey();

    /**
     * Set value
     *
     * @param string $value
     * @return CategoryMetaInterface
     */
    public function setValue($value);

    /**
     * Get value
     *
     * @return string
     */
    public function getValue();

    /**
     * Set locale
     *
     * @param string $locale
     * @return CategoryMetaInterface
     */
    public function setLocale($locale);

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set category
     *
     * @param CategoryInterface $category
     * @return CategoryMetaInterface
     */
    public function setCategory(CategoryInterface $category);

    /**
     * Get category
     *
     * @return CategoryInterface
     */
    public function getCategory();
}
