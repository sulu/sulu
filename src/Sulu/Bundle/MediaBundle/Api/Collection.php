<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Api;

use Sulu\Bundle\CoreBundle\Entity\ApiEntityWrapper;
use Sulu\Bundle\MediaBundle\Entity\Collection as Entity;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Expose;
use DateTime;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * Class Collection
 * The Collection RestObject is the api entity for the CollectionController.
 * @package Sulu\Bundle\MediaBundle\Media\RestObject
 * @ExclusionPolicy("all")
 */
class Collection extends ApiEntityWrapper
{

    /**
     * @var array
     */
    protected $previews = array();

    /**
     * @var array
     */
    protected $properties = array();

    /**
     * @var string
     */
    protected $locale;


    public function __construct(Entity $collection, $locale)
    {
        $this->entity = $collection;
        $this->locale = $locale;
    }

    /**
     * @VirtualProperty
     * @SerializedName("children")
     * @return array
     */
    public function getChildren()
    {
        $childIds = array();
        /**
         * @var Entity $child
         */
        foreach ($this->entity->getChildren() as $child) {
            array_push($childIds, $child->getId());
        }

        return $childIds;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $metaExists = false;

        /**
         * @var CollectionMeta $meta
         */
        foreach ($this->entity->getMeta() as $meta) {
            if ($meta->getLocale() == $this->locale) {
                $metaExists = true;
                $meta->setDescription($description);
            }
        }

        if (!$metaExists) {
            $meta = new CollectionMeta();
            $meta->setDescription($description);
            $meta->setCollection($this->entity);
            $meta->setLocale($this->locale);
            $this->entity->addMeta($meta);
        }

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("description")
     * @return string
     */
    public function getDescription()
    {
        $description = null;
        $counter = 0;

        /**
         * @var CollectionMeta $meta
         */
        foreach ($this->entity->getMeta() as $meta) {
            $counter++;
            // when meta not exists in locale return first created description
            if ($meta->getLocale() == $this->locale || $counter == 1) {
                $description = $meta->getDescription();
            }
        }

        return $description;
    }

    /**
     * @VirtualProperty
     * @SerializedName("id")
     * @return int
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("mediaNumber")
     * @return int
     */
    public function getMediaNumber()
    {
        $mediaCount = 0;

        /**
         * @var Entity $child
         */
        foreach ($this->entity->getChildren() as $child) {
            array_push($childIds, $child->getId());
            $mediaCount += count($child->getMedia());
        }

        // set media count
        $mediaCount += count($this->entity->getMedia());

        return $mediaCount;
    }

    /**
     * @param Collection $parent
     * @return $this
     */
    public function setParent($parent)
    {
        $this->entity->setParent($parent);
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("parent")
     * @return int|null
     */
    public function getParent()
    {
        $parent = $this->entity->getParent();
        if ($parent) {
            return $parent->getId();
        }
        return null;
    }

    /**
     * @param array $style
     * @return $this
     */
    public function setStyle($style)
    {
        if (!is_string($style)) {
            $style = json_encode($style);
        }
        $this->entity->setStyle($style);
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("style")
     * @return array
     */
    public function getStyle()
    {
        return json_decode($this->entity->getStyle(), true);
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("properties")
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getPreviews()
    {
        return $this->previews;
    }

    /**
     * @VirtualProperty
     * @SerializedName("thumbnails")
     */
    public function getThumbnails() // FIXME change to getPreviews when SerializedName working
    {
        return $this->previews;
    }

    /**
     * @param array $previews
     * @return $this
     */
    public function setPreviews($previews)
    {
        $this->previews = $previews;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $metaExists = false;

        /**
         * @var CollectionMeta $meta
         */
        foreach ($this->entity->getMeta() as $meta) {
            if ($meta->getLocale() == $this->locale) {
                $metaExists = true;
                $meta->setTitle($title);
            }
        }

        if (!$metaExists) {
            $meta = new CollectionMeta();
            $meta->setTitle($title);
            $meta->setCollection($this->entity);
            $meta->setLocale($this->locale);
            $this->entity->addMeta($meta);
        }

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("title")
     * @return string
     */
    public function getTitle()
    {
        $title = null;
        $counter = 0;

        /**
         * @var CollectionMeta $meta
         */
        foreach ($this->entity->getMeta() as $meta) {
            $counter++;
            // when meta not exists in set locale return first created title
            if ($meta->getLocale() == $this->locale || $counter == 1) {
                $title = $meta->getTitle();
            }
        }

        return $title;
    }

    /**
     * @param CollectionType $type
     * @return $this
     */
    public function setType($type)
    {
        $this->entity->setType($type);
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("type")
     * @return int
     */
    public function getType()
    {
        $typeId = null;
        $type = $this->entity->getType();
        if ($type) {
            $typeId = $type->getId();
        }

        return $typeId;
    }

    /**
     * @param DateTime|string $changed
     * @return $this
     */
    public function setChanged($changed)
    {
        if (is_string($changed)) {
            $changed = new DateTime($changed);
        }
        $this->entity->setChanged($changed);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("changed")
     * @return string
     */
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * @param string $changer
     * @return $this
     */
    public function setChanger($changer)
    {
        $this->entity->setChanger($changer);
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("changer")
     * @return string
     */
    public function getChanger()
    {
        return $this->entity->getChanger();
    }

    /**
     * @param DateTime|string $created
     * @return $this
     */
    public function setCreated($created)
    {
        if (is_string($created)) {
            $created = new DateTime($created);
        }
        $this->entity->setCreated($created);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("created")
     * @return string
     */
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * @param string $creator
     * @return $this
     */
    public function setCreator($creator)
    {
        $this->entity->setChanger($creator);
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("creator")
     * @return string
     */
    public function getCreator()
    {
        return $this->entity->getCreator();
    }

} 
