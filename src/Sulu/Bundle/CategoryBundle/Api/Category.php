<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Api;

use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Groups;
use Sulu\Component\Category\Model\CategoryInterface;
use Sulu\Component\Category\Model\CategoryMeta;
use Sulu\Component\Category\Model\CategoryMetaInterface;
use Sulu\Component\Category\Model\CategoryTranslation;
use Sulu\Component\Category\Model\CategoryTranslationInterface;
use Sulu\Component\Rest\ApiWrapper;

/**
 * The Category class which will be exported to the API
 *
 * @package Sulu\Bundle\CategoryBundle\Api
 * @Relation("self", href="expr('/api/admin/categories/' ~ object.getId())")
 * @ExclusionPolicy("all")
 */
class Category extends ApiWrapper
{

    /**
     * @param CategoryInterface $category
     * @param string $locale The locale of this category
     */
    public function __construct(CategoryInterface $category, $locale)
    {
        $this->entity = $category;
        $this->locale = $locale;
    }

    /**
     * Returns the id of the category
     * @VirtualProperty
     * @SerializedName("id")
     * @return int|string
     * @Groups({"fullCategory", "partialCategory"})
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Returns the key of the category
     * @VirtualProperty
     * @SerializedName("key")
     * @return string
     * @Groups({"fullCategory", "partialCategory"})
     */
    public function getKey()
    {
        return $this->entity->getKey();
    }

    /**
     * Returns the name of the Category dependent on the locale
     * @VirtualProperty
     * @SerializedName("name")
     * @return string
     * @Groups({"fullCategory","partialCategory"})
     */
    public function getName()
    {
        return $this->getTranslation()->getTranslation();
    }

    /**
     * Returns the name of the Category dependent on the locale
     * @VirtualProperty
     * @SerializedName("meta")
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

        return null;
    }

    /**
     * Returns the creator of the category
     * @VirtualProperty
     * @SerializedName("creator")
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
     * Returns the changer of the category
     * @VirtualProperty
     * @SerializedName("changer")
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
     * Returns the created date for the category
     * @VirtualProperty
     * @SerializedName("created")
     * @return string
     * @Groups({"fullCategory"})
     */
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * Returns the created date for the category
     * @VirtualProperty
     * @SerializedName("created")
     * @return string
     * @Groups({"fullCategory"})
     */
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * Returns the children of a category
     * @VirtualProperty
     * @SerializedName("children")
     * @return array
     * @Groups({"fullCategory"})
     */
    public function getChildren()
    {
        $children = [];
        if ($this->entity->getChildren()) {
            foreach ($this->entity->getChildren() as $child) {
                $children[] = new Category($child, $this->locale);
            }
        }

        return $children;
    }

    /**
     * Takes a name as string and sets it to the entity
     *
     * @param string $name
     * @return \Sulu\Bundle\CategoryBundle\Api\Category
     */
    public function setName($name)
    {
        $this->getTranslation()->setTranslation($name);

        return $this;
    }

    /**
     * Takes meta as array and sets it to the entity
     *
     * @param array $meta
     * @return \Sulu\Bundle\CategoryBundle\Api\Category
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
     * Sets a given category as the parent of the entity
     *
     * @param CategoryInterface $parent
     * @return \Sulu\Bundle\CategoryBundle\Api\Category
     */
    public function setParent($parent)
    {
        $this->entity->setParent($parent);

        return $this;
    }

    /**
     * Sets the key of the category
     *
     * @param string $key
     * @return \Sulu\Bundle\CategoryBundle\Api\Category
     */
    public function setKey($key)
    {
        $this->entity->setKey($key);

        return $this;
    }

    /**
     * Takes a user entity and returns the fullname
     * @param $user
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
     * Takes an array of CollectionMeta and returns a single meta for a given id
     * @param $meta
     * @param $id
     * @return CategoryMetaInterface
     */
    private function getSingleMetaById($meta, $id)
    {
        $return = null;
        foreach ($meta as $singleMeta) {
            /** @var CategoryMetaInterface $singleMeta */
            if ($singleMeta->getId() === $id) {
                $return = $singleMeta;
                break;
            }
        }

        return $return;
    }

    /**
     * Returns an array representation of the object
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'key' => $this->getKey(),
            'name' => $this->getName(),
            'meta' => $this->getMeta(),
            'creator' => $this->getCreator(),
            'changer' => $this->getChanger(),
            'created' => $this->getCreated(),
            'changed' => $this->getChanged()
        );
    }

    /**
     * Returns the translation with the given locale
     * @return CategoryTranslationInterface
     */
    public function getTranslation()
    {
        foreach ($this->entity->getTranslations() as $translation) {
            if ($translation->getLocale() == $this->locale) {
                return $translation;
            }
        }
        $translation = new CategoryTranslation();
        $translation->setLocale($this->locale);
        $translation->setCategory($this->entity);

        $this->entity->addTranslation($translation);

        return $translation;
    }
}
