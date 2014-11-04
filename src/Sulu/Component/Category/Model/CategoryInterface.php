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

use Doctrine\Common\Collections\Collection;
use Sulu\Component\Security\UserInterface;

interface CategoryInterface
{
    /**
     * Get id
     *
     * @return int|string
     */
    public function getId();

    /**
     * Set key
     *
     * @param string $key
     * @return Category
     */
    public function setKey($key);

    /**
     * Get key
     *
     * @return string
     */
    public function getKey();

    /**
     * Add meta
     *
     * @param CategoryMetaInterface $meta
     * @return Category
     */
    public function addMeta(CategoryMetaInterface $meta);

    /**
     * Remove meta
     *
     * @param CategoryMetaInterface $meta
     */
    public function removeMeta(CategoryMetaInterface $meta);

    /**
     * Get meta
     *
     * @return Collection
     */
    public function getMeta();

    /**
     * Add translation
     *
     * @param CategoryTranslationInterface $translation
     * @return Category
     */
    public function addTranslation(CategoryTranslationInterface $translation);

    /**
     * Remove translation
     *
     * @param CategoryTranslationInterface $translation
     */
    public function removeTranslation(CategoryTranslationInterface $translation);

    /**
     * Get translations
     *
     * @return Collection
     */
    public function getTranslations();

    /**
     * Add children
     *
     * @param CategoryInterface $children
     * @return Category
     */
    public function addChildren(CategoryInterface $children);

    /**
     * Remove children
     *
     * @param CategoryInterface $children
     */
    public function removeChildren(CategoryInterface $children);

    /**
     * Get children
     *
     * @return Collection
     */
    public function getChildren();

    /**
     * Set parent
     *
     * @param CategoryInterface $parent
     * @return Category
     */
    public function setParent(CategoryInterface $parent = null);

    /**
     * Get parent
     *
     * @return CategoryInterface
     */
    public function getParent();

    /**
     * Set depth
     *
     * @param integer $depth
     * @return Category
     */
    public function setDepth($depth);

    /**
     * Get depth
     *
     * @return integer
     */
    public function getDepth();

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Category
     */
    public function setCreated($created);

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return Category
     */
    public function setChanged($changed);

    /**
     * Get changed
     *
     * @return \DateTime
     */
    public function getChanged();

    /**
     * Set creator
     *
     * @param UserInterface $creator
     * @return Category
     */
    public function setCreator(UserInterface $creator = null);

    /**
     * Get creator
     *
     * @return UserInterface
     */
    public function getCreator();

    /**
     * Set changer
     *
     * @param UserInterface $changer
     * @return Category
     */
    public function setChanger(UserInterface $changer = null);

    /**
     * Get changer
     *
     * @return UserInterface
     */
    public function getChanger();
}
