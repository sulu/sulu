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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Category.
 */
interface CategoryInterface extends AuditableInterface
{
    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CategoryInterface
     */
    public function setId($id);

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return CategoryInterface
     */
    public function setLft($lft);

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft();

    /**
     * Set rgt.
     *
     * @param int $rgt
     *
     * @return CategoryInterface
     */
    public function setRgt($rgt);

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt();

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return CategoryInterface
     */
    public function setDepth($depth);

    /**
     * Get depth.
     *
     * @return int
     */
    public function getDepth();

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * Get key.
     *
     * @return string
     */
    public function getKey();

    /**
     * Set key.
     *
     * @param string $key
     *
     * @return CategoryInterface
     */
    public function setKey($key);

    /**
     * Set defaultLocale.
     *
     * @param string $defaultLocale
     *
     * @return CategoryInterface
     */
    public function setDefaultLocale($defaultLocale);

    /**
     * Get defaultLocale.
     *
     * @return string
     */
    public function getDefaultLocale();

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged();

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Add meta.
     *
     * @param CategoryMetaInterface $meta
     *
     * @return CategoryInterface
     */
    public function addMeta(CategoryMetaInterface $meta);

    /**
     * Remove meta.
     *
     * @param CategoryMetaInterface $meta
     */
    public function removeMeta(CategoryMetaInterface $meta);

    /**
     * Get meta.
     *
     * @return Collection
     */
    public function getMeta();

    /**
     * Add translations.
     *
     * @param CategoryTranslationInterface $translations
     *
     * @return CategoryInterface
     */
    public function addTranslation(CategoryTranslationInterface $translations);

    /**
     * Remove translations.
     *
     * @param CategoryTranslationInterface $translations
     */
    public function removeTranslation(CategoryTranslationInterface $translations);

    /**
     * Get translations.
     *
     * @return Collection
     */
    public function getTranslations();

    /**
     * Get single meta by locale.
     *
     * @param $locale
     *
     * @return CategoryTranslationInterface
     */
    public function findTranslationByLocale($locale);

    /**
     * {@see Category::addChild}.
     *
     * @deprecated use Category::addChild instead
     */
    public function addChildren(CategoryInterface $child);

    /**
     * Add children.
     *
     * @param CategoryInterface $child
     *
     * @return CategoryInterface
     */
    public function addChild(CategoryInterface $child);

    /**
     * {@see Category::removeChild}.
     *
     * @deprecated use Category::addChild instead
     */
    public function removeChildren(CategoryInterface $child);

    /**
     * Remove children.
     *
     * @param CategoryInterface $child
     */
    public function removeChild(CategoryInterface $child);

    /**
     * Get children.
     *
     * @return Collection
     */
    public function getChildren();

    /**
     * Set parent.
     *
     * @param CategoryInterface $parent
     *
     * @return CategoryInterface
     */
    public function setParent(CategoryInterface $parent = null);

    /**
     * Get parent.
     *
     * @return CategoryInterface
     */
    public function getParent();

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return CategoryInterface
     */
    public function setCreator(UserInterface $creator = null);

    /**
     * Get creator.
     *
     * @return UserInterface
     */
    public function getCreator();

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return CategoryInterface
     */
    public function setChanger(UserInterface $changer = null);

    /**
     * Set changed.
     *
     * @param \DateTime $changed
     *
     * @return CategoryInterface
     */
    public function setChanged(\DateTime $changed);

    /**
     * Get changer.
     *
     * @return UserInterface
     */
    public function getChanger();
}
