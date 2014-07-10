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

use Doctrine\ORM\EntityNotFoundException;
use Sulu\Bundle\CoreBundle\Entity\ApiEntityWrapper;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage;
use Sulu\Bundle\MediaBundle\Entity\Media as Entity;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TagBundle\Entity\Tag;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * Class Media
 * The Media RestObject is the api entity for the MediaController.
 * @package Sulu\Bundle\MediaBundle\Media\RestObject
 * @ExclusionPolicy("all")
 */
class Media extends ApiEntityWrapper
{

    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $formats = array();

    /**
     * @var array
     */
    protected $properties = array();

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var FileVersion
     */
    protected $fileVersion = null;


    public function __construct(Entity $collection, $locale, $version = null)
    {
        $this->entity = $collection;
        $this->locale = $locale;
        $this->version = $version;
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
     * @SerializedName("locale")
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param Collection $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->entity->setCollection($collection);
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("collection")
     * @return int
     */
    public function getCollection()
    {
        $collection = $this->entity->getCollection();
        if ($collection) {
            return $collection->getId();
        }
        return null;
    }

    /**
     * @param int $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->getFileVersion()->setSize($size);
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("size")
     * @return int
     */
    public function getSize()
    {
        return $this->getFileVersion()->getSize();
    }

    /**
     * @param string $mimeType
     * @return $this
     */
    public function setMimeType($mimeType)
    {
        $this->getFileVersion()->setMimeType($mimeType);
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("mimeType")
     * @return string
     */
    public function getMimeType()
    {
        return $this->getFileVersion()->getMimeType();
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $metaExists = false;

        $fileVersion = $this->getFileVersion();
        /**
         * @var FileVersionMeta $meta
         */
        foreach ($fileVersion->getMeta() as $meta) {
            if ($meta->getLocale() == $this->locale) {
                $metaExists = true;
                $meta->setTitle($title);
            }
        }

        if (!$metaExists) {
            $meta = new FileVersionMeta();
            $meta->setTitle($title);
            $meta->setLocale($this->locale);
            $meta->setFileVersion($fileVersion);
            $fileVersion->addMeta($meta);
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
         * @var FileVersionMeta $meta
         */
        foreach ($this->getFileVersion()->getMeta() as $meta) {
            $counter++;
            // when meta not exists in locale return first created description
            if ($meta->getLocale() == $this->locale || $counter == 1) {
                $title = $meta->getTitle();
            }
        }

        return $title;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $metaExists = false;

        $fileVersion = $this->getFileVersion();
        /**
         * @var FileVersionMeta $meta
         */
        foreach ($fileVersion->getMeta() as $meta) {
            if ($meta->getLocale() == $this->locale) {
                $metaExists = true;
                $meta->setDescription($description);
            }
        }

        if (!$metaExists) {
            $meta = new FileVersionMeta();
            $meta->setDescription($description);
            $meta->setLocale($this->locale);
            $meta->setFileVersion($fileVersion);
            $fileVersion->addMeta($meta);
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
         * @var FileVersionMeta $meta
         */
        foreach ($this->getFileVersion()->getMeta() as $meta) {
            $counter++;
            // when meta not exists in locale return first created description
            if ($meta->getLocale() == $this->locale || $counter == 1) {
                $description = $meta->getDescription();
            }
        }

        return $description;
    }

    /**
     * @param int $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("version")
     * @return int
     */
    public function getVersion()
    {
        return $this->getFileVersion()->getVersion();
    }

    /**
     * @VirtualProperty
     * @SerializedName("versions")
     * @return array
     */
    public function getVersions()
    {
        $versions = array();
        /**
         * @var File $file
         */
        foreach ($this->entity->getFiles() as $file) {
            /**
             * @var FileVersion $fileVersion
             */
            foreach ($file->getFileVersions() as $fileVersion) {
                array_push($versions, $fileVersion->getVersion());
            }
            break; // currently only one file per media exists
        }
        return $versions;
    }

    /**
     * @param int $name
     * @return $this
     */
    public function setName($name)
    {
        $this->getFileVersion()->setName($name);
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("name")
     * @return int
     */
    public function getName()
    {
        return $this->getFileVersion()->getName();
    }

    /**
     * @VirtualProperty
     * @SerializedName("type")
     * @return int
     */
    public function getType()
    {
        $type = $this->entity->getType();
        if ($type) {
            return $type->getId();
        }
        return null;
    }

    /**
     * @param MediaType $type
     * @return $this
     */
    public function setType($type)
    {
        $this->entity->setType($type);
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("storageOptions")
     * @return string
     */
    public function getStorageOptions()
    {
        return $this->getFileVersion()->getStorageOptions();
    }

    /**
     * @param string $storageOptions
     * @return $this
     */
    public function setStorageOptions($storageOptions)
    {
        $this->getFileVersion()->setStorageOptions($storageOptions);
        return $this;
    }

    /**
     * @param array $publishLanguages
     * @return $this
     */
    public function setPublishLanguages($publishLanguages)
    {
        /**
         * @var FileVersionPublishLanguage $publishLanguage
         */
        $fileVersion = $this->getFileVersion();
        foreach ($publishLanguages as $key => $locale) {
            foreach ($fileVersion->getPublishLanguages() as $publishLanguage) {
                if ($publishLanguage->getLocale() == $locale) {
                    unset($publishLanguages[$key]);
                    break;
                }
            }
        }

        foreach ($publishLanguages as $locale) {
            $publishLanguage = new FileVersionPublishLanguage();
            $publishLanguage->setFileVersion($fileVersion);
            $publishLanguage->setLocale($locale);
            $fileVersion->addPublishLanguage($publishLanguage);
        }
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("publishLanguages")
     * @return array
     */
    public function getPublishLanguages()
    {
        $publishLanguages = array();

        /**
         * @var FileVersionPublishLanguage $publishLanguage
         */
        foreach ($this->getFileVersion()->getPublishLanguages() as $publishLanguage) {
            array_push($publishLanguages, $publishLanguage->getLocale());
        }

        return $publishLanguages;
    }

    /**
     * @param array $contentLanguages
     * @return $this
     */
    public function setContentLanguages($contentLanguages)
    {
        $fileVersion = $this->getFileVersion();
        /**
         * @var FileVersionContentLanguage $contentLanguage
         */
        foreach ($contentLanguages as $key => $locale) {
            foreach ($fileVersion->getContentLanguages() as $contentLanguage) {
                if ($contentLanguage->getLocale() == $locale) {
                    unset($contentLanguages[$key]);
                    break;
                }
            }
        }

        foreach ($contentLanguages as $locale) {
            $contentLanguage = new FileVersionContentLanguage();
            $contentLanguage->setFileVersion($fileVersion);
            $contentLanguage->setLocale($locale);
            $fileVersion->addContentLanguage($contentLanguage);
        }
        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("contentLanguages")
     * @return array
     */
    public function getContentLanguages()
    {
        $contentLanguages = array();
        /**
         * @var FileVersionContentLanguage $contentLanguage
         */
        foreach ($this->getFileVersion()->getContentLanguages() as $contentLanguage) {
            array_push($contentLanguages, $contentLanguage->getLocale());
        }

        return $contentLanguages;
    }

    /**
     * @param array $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $fileVersion = $this->getFileVersion();
        foreach ($tags as $key => $tagName) {
            /**
             * @var Tag $tag
             */
            foreach ($fileVersion->getTags() as $tag) {
                if ($tag->getName() == $tagName) {
                    unset($tags[$key]);
                    break;
                }
            }
        }

        foreach ($tags as $tagName) {
            $tag = new Tag();
            $tag->setName($tagName);
            $fileVersion->addTag($tag);
        }

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("tags")
     * @return array
     */
    public function getTags()
    {
        $tags = array();

        /**
         * @var Tag $tag
         */
        foreach ($this->getFileVersion()->getTags() as $tag) {
            $tags[$tag->getId()] = $tag->getName();
        }
        return $tags;
    }

    /**
     * @return array
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * @VirtualProperty
     * @SerializedName("thumbnails")
     * @return array
     */
    public function getThumbnails() // FIXME change to getPreviews when SerializedName working
    {
        return $this->formats;
    }

    /**
     * @param array $formats
     */
    public function setFormats($formats)
    {
        $this->formats = $formats;
    }

    /**
     * @VirtualProperty
     * @SerializedName("url")
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @VirtualProperty
     * @SerializedName("changed")
     * @return string
     */
    public function getChanged()
    {
        return $this->getFileVersion()->getChanged();
    }

    /**
     * @VirtualProperty
     * @SerializedName("changer")
     * @return string
     */
    public function getChanger()
    {
        $changer = $this->getFileVersion()->getChanger();
        if (method_exists($changer, 'getFullName')) {
            return $changer->getFullName();
        }
        return null;
    }

    /**
     * @VirtualProperty
     * @SerializedName("created")
     * @return mixed
     */
    public function getCreated()
    {
        return $this->getFileVersion()->getCreated();
    }

    /**
     * @VirtualProperty
     * @SerializedName("creator")
     * @return string
     */
    public function getCreator()
    {
        $creator = $this->fileVersion->getCreator();
        if (method_exists($creator, 'getFullName')) {
            return $creator->getFullName();
        }
        return null;
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
     * @return FileVersion
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getFileVersion()
    {
        if ($this->fileVersion !== null) {
            return $this->fileVersion;
        }

        /**
         * @var File $file
         */
        foreach ($this->entity->getFiles() as $file) {
            if ($this->version !== null) {
                $version = $this->version;
            } else {
                $version = $file->getVersion();
            }
            /**
             * @var FileVersion $fileVersion
             */
            foreach ($file->getFileVersions() as $fileVersion) {
                if ($fileVersion->getVersion() == $version) {
                    $this->fileVersion = $fileVersion;
                    return $fileVersion;
                }
            }
            break; // currently only one file per media exists
        }
        throw new EntityNotFoundException('SuluMediaBundle:FileVersion', $this->entity->getId());
    }

} 
