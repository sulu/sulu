<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Api;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CategoryBundle\Entity\Category as Entity;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMeta;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CoreBundle\Entity\ApiEntityWrapper;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;

class Category extends ApiEntityWrapper
{
    public function __construct(Entity $category, $locale)
    {
        $this->entity = $category;
        $this->locale = $locale;
    }

    /**
     * Returns the id of the category.
     *
     * @VirtualProperty
     * @SerializedName("id")
     *
     * @return array
     * @Groups({"fullCategory","partialCategory"})
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Returns the key of the category.
     *
     * @VirtualProperty
     * @SerializedName("key")
     *
     * @return string
     * @Groups({"fullCategory","partialCategory"})
     */
    public function getKey()
    {
        return $this->entity->getKey();
    }

    /**
     * Returns the name of the Category dependent on the locale.
     *
     * @VirtualProperty
     * @SerializedName("name")
     * @Groups({"fullCategory","partialCategory"})
     *
     * @return string
     */
    public function getName()
    {
        if (($translation = $this->getTranslation()) === null) {
            return;
        }

        return $translation->getTranslation();
    }

    /**
     * Returns the name of the Category dependent on the locale.
     *
     * @VirtualProperty
     * @SerializedName("meta")
     *
     * @return array
     * @Groups({"fullCategory","partialCategory"})
     */
    public function getMeta()
    {
        $arrReturn = [];
        if ($this->entity->getMeta() !== null) {
            foreach ($this->entity->getMeta() as $meta) {
                if (!$meta->getLocale() || $meta->getLocale() === $this->locale) {
                    array_push(
                        $arrReturn,
                        [
                            'id' => $meta->getId(),
                            'key' => $meta->getKey(),
                            'value' => $meta->getValue(),
                        ]
                    );
                }
            }

            return $arrReturn;
        }

        return;
    }

    /**
     * Returns the creator of the category.
     *
     * @VirtualProperty
     * @SerializedName("creator")
     *
     * @return string
     */
    public function getCreator()
    {
        $strReturn = '';
        $creator = $this->entity->getCreator();
        if ($creator) {
            return $this->getUserFullName($creator);
        }

        return $strReturn;
    }

    /**
     * Returns the changer of the category.
     *
     * @VirtualProperty
     * @SerializedName("changer")
     *
     * @return string
     */
    public function getChanger()
    {
        $strReturn = '';
        $changer = $this->entity->getChanger();
        if ($changer) {
            return $this->getUserFullName($changer);
        }

        return $strReturn;
    }

    /**
     * Returns the created date for the category.
     *
     * @VirtualProperty
     * @SerializedName("created")
     * @Groups({"fullCategory"})
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * Returns the created date for the category.
     *
     * @VirtualProperty
     * @SerializedName("created")
     * @Groups({"fullCategory"})
     *
     * @return string
     */
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * Returns the children of a category.
     *
     * @VirtualProperty
     * @SerializedName("children")
     * @Groups({"fullCategory"})
     *
     * @return Category[]
     */
    public function getChildren()
    {
        $children = [];
        if ($this->entity->getChildren()) {
            foreach ($this->entity->getChildren() as $child) {
                $children[] = new self($child, $this->locale);
            }
        }

        return $children;
    }

    /**
     * Takes a name as string and sets it to the entity.
     *
     * @param string $name
     *
     * @return Category
     */
    public function setName($name)
    {
        $translation = $this->getTranslation();
        if ($translation === null) {
            $translation = $this->createTranslation();
        }

        $translation->setTranslation($name);

        return $this;
    }

    /**
     * Takes meta as array and sets it to the entity.
     *
     * @param array $meta
     *
     * @return Category
     */
    public function setMeta($meta)
    {
        $meta = (is_array($meta)) ? $meta : [];
        $currentMeta = $this->entity->getMeta();
        foreach ($meta as $singleMeta) {
            $metaEntity = null;
            if (isset($singleMeta['id'])) {
                $metaEntity = $this->getSingleMetaById($currentMeta, $singleMeta['id']);
            }
            if (!$metaEntity) {
                $metaEntity = new CategoryMeta();
                $metaEntity->setCategory($this->entity);
                $this->entity->addMeta($metaEntity);
            }

            $metaEntity->setKey($singleMeta['key']);
            $metaEntity->setValue($singleMeta['value']);
            if (array_key_exists('locale', $singleMeta)) {
                $metaEntity->setLocale($singleMeta['locale']);
            }
        }

        return $this;
    }

    /**
     * Returns a parent category if one exists.
     *
     * @return null|Category
     */
    public function getParent()
    {
        $parent = $this->getEntity()->getParent();
        if ($parent) {
            return new self($parent, $this->locale);
        }

        return;
    }

    /**
     * Sets a given category as the parent of the entity.
     *
     * @param Entity $parent
     *
     * @return Category
     */
    public function setParent($parent)
    {
        $this->entity->setParent($parent);

        return $this;
    }

    /**
     * Sets the key of the category.
     *
     * @param string $key
     *
     * @return Category
     */
    public function setKey($key)
    {
        $this->entity->setKey($key);

        return $this;
    }

    /**
     * Takes a user entity and returns the fullname.
     *
     * @param $user
     *
     * @return string
     */
    private function getUserFullName($user)
    {
        $strReturn = '';
        if ($user && method_exists($user, 'getContact')) {
            $strReturn = $user->getContact()->getFirstName() . ' ' . $user->getContact()->getLastName();
        }

        return $strReturn;
    }

    /**
     * Takes an array of CollectionMeta and returns a single meta for a given id.
     *
     * @param $meta
     * @param $id
     *
     * @return CollectionMeta
     */
    private function getSingleMetaById($meta, $id)
    {
        $return = null;
        foreach ($meta as $singleMeta) {
            if ($singleMeta->getId() === $id) {
                $return = $singleMeta;
                break;
            }
        }

        return $return;
    }

    /**
     * Returns an array representation of the object.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'key' => $this->getKey(),
            'name' => $this->getName(),
            'meta' => $this->getMeta(),
            'creator' => $this->getCreator(),
            'changer' => $this->getChanger(),
            'created' => $this->getCreated(),
            'changed' => $this->getChanged(),
        ];
    }

    /**
     * Returns the translation with the given locale.
     *
     * @return CategoryTranslation
     */
    private function getTranslation()
    {
        foreach ($this->entity->getTranslations() as $translation) {
            if ($translation->getLocale() == $this->locale) {
                return $translation;
            }
        }

        return;
    }

    /**
     * Creates a new Translation for category.
     *
     * @return CategoryTranslation
     */
    private function createTranslation()
    {
        $translation = new CategoryTranslation();
        $translation->setLocale($this->locale);
        $translation->setCategory($this->entity);

        $this->entity->addTranslation($translation);

        return $translation;
    }
}
