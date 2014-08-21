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
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\TagBundle\Entity\Tag;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\ExclusionPolicy;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\UserInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;

/**
 * Class Media
 * The Media RestObject is the api entity for the MediaController.
 * @package Sulu\Bundle\MediaBundle\Media\RestObject
 * @ExclusionPolicy("all")
 */
class Media extends ApiWrapper
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
     * @var TagManagerInterface
     */
    protected $tagManager;

    /**
     * @var FileVersion
     */
    protected $fileVersion = null;

    public function __construct(Entity $media, $locale, $version = null, TagManagerInterface $tagManager)
    {
        $this->entity = $media;
        $this->locale = $locale;
        $this->version = $version;
        $this->tagManager = $tagManager;
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
        $this->getMeta(true)->setTitle($title);

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
        /** @var FileVersionMeta $meta */
        foreach ($this->getFileVersion()->getMeta() as $key => $meta) {
            // get title of the meta in locale, when not exists return title of the first meta
            if ($meta->getLocale() == $this->locale || $key == 0) {
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
        $this->getMeta(true)->setDescription($description);

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
        /** @var FileVersionMeta $meta */
        foreach ($this->getFileVersion()->getMeta() as $key => $meta) {
            // get description of the meta in locale, when not exists return description of the first meta
            if ($meta->getLocale() == $this->locale || $key == 0) {
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
        /** @var File $file */
        foreach ($this->entity->getFiles() as $file) {
            /** @var FileVersion $fileVersion */
            foreach ($file->getFileVersions() as $fileVersion) {
                $versions[] = $fileVersion->getVersion();
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
     * @return MediaType
     */
    public function getType()
    {
        return $this->entity->getType();
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
        $fileVersion = $this->getFileVersion();

        foreach ($publishLanguages as $locale) {
            $publishLanguage = new FileVersionPublishLanguage();
            $publishLanguage->setFileVersion($fileVersion);
            $publishLanguage->setLocale($locale);
            if(!$fileVersion->getPublishLanguages()->contains($publishLanguage)) {
                $fileVersion->addPublishLanguage($publishLanguage);
            }
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
        /** @var FileVersionPublishLanguage $publishLanguage */
        foreach ($this->getFileVersion()->getPublishLanguages() as $publishLanguage) {
            $publishLanguages[] = $publishLanguage->getLocale();
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

        foreach ($contentLanguages as $locale) {
            $contentLanguage = new FileVersionContentLanguage();
            $contentLanguage->setFileVersion($fileVersion);
            $contentLanguage->setLocale($locale);
            if (!$fileVersion->getContentLanguages()->contains($contentLanguage)) {
                $fileVersion->addContentLanguage($contentLanguage);
            }
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
        /** @var FileVersionContentLanguage $contentLanguage */
        foreach ($this->getFileVersion()->getContentLanguages() as $contentLanguage) {
            $contentLanguages[] = $contentLanguage->getLocale();
        }

        return $contentLanguages;
    }

    /**
     * @param \Doctrine\ $tags
     * @param number $userId
     * @return $this
     */
    public function setTags($tags, $userId)
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->removeTags();
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $tagEntity = $this->tagManager->findOrCreateByName($tag, $userId);
            if (!$fileVersion->getTags()->contains($tagEntity)) {
                $fileVersion->addTag($tagEntity);
            }
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
        foreach($this->getFileVersion()->getTags() as $tag) {
            array_push($tags, $tag->getName());
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
     * @param DateTime|string $changed
     * @return $this
     */
    public function setChanged($changed)
    {
        if (is_string($changed)) {
            $changed = new \DateTime($changed);
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
        return $this->getFileVersion()->getChanged();
    }

    /**
     * @param UserInterface $changer
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
        $user = $this->getFileVersion()->getChanger();
        if ($user) {
            return $user->getFullName();
        }
        return null;
    }

    /**
     * @param DateTime|string $created
     * @return $this
     */
    public function setCreated($created)
    {
        if (is_string($created)) {
            $created = new \DateTime($created);
        }
        $this->entity->setCreated($created);

        return $this;
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
     * @param UserInterface $creator
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
        $user = $this->getFileVersion()->getCreator();
        if ($user) {
            return $user->getFullName();
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
     * @VirtualProperty
     * @SerializedName("downloadCounter")
     * @return string
     */
    public function getDownloadCounter()
    {
        $downloadCounter = 0;
        foreach ($this->getEntity()->getFiles() as $file) {
            /** @var FileVersion $fileVersion */
            foreach ($file->getFileVersions() as $fileVersion) {
                $downloadCounter += intval($fileVersion->getDownloadCounter());
            }
        }
        return $downloadCounter;
    }

    /**
     * Returns array representation of media
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'locale' => $this->getLocale(),
            'collection' => $this->getCollection(),
            'size' => $this->getSize(),
            'mimeType' => $this->getMimeType(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'version' => $this->getVersion(),
            'name' => $this->getName(),
            'storageOptions' => $this->getStorageOptions(),
            'publishLanguages' => $this->getPublishLanguages(),
            'tags' => $this->getTags(),
            'thumbnails' => $this->getThumbnails(),
            'url' => $this->getUrl(),
            'changed' => $this->getChanged(),
            'changer' => $this->getChanger(),
            'created' => $this->getCreated(),
            'creator' => $this->getCreator(),
            'downloadCounter' => $this->getDownloadCounter()
        );
    }

    /**
     * @return FileVersion
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException
     */
    private function getFileVersion()
    {
        if ($this->fileVersion !== null) {
            return $this->fileVersion;
        }

        /** @var File $file */
        foreach ($this->entity->getFiles() as $file) {
            if ($this->version !== null) {
                $version = $this->version;
            } else {
                $version = $file->getVersion();
            }
            /** @var FileVersion $fileVersion */
            foreach ($file->getFileVersions() as $fileVersion) {
                if ($fileVersion->getVersion() == $version) {
                    $this->fileVersion = $fileVersion;
                    return $fileVersion;
                }
            }
            break; // currently only one file per media exists
        }
        throw new FileVersionNotFoundException($this->entity->getId(), $this->version);
    }

    /**
     * @param bool $create
     * @return FileVersionMeta
     */
    private function getMeta($create = false)
    {
        $locale = $this->locale;
        $metaCollection = $this->getFileVersion()->getMeta();

        // get meta only with this locale
        $metaCollectionFiltered = $metaCollection->filter(function($meta) use ($locale) {
            /** @var FileVersionMeta $meta */
            if ($meta->getLocale() == $locale) {
                return true;
            }
            return false;
        });

        // check if meta was found
        if ($metaCollectionFiltered->isEmpty()) {
            if ($create) {
                // create when not found
                $meta = new FileVersionMeta();
                $meta->setLocale($this->locale);
                $meta->setFileVersion($this->getFileVersion());
                $this->getFileVersion()->addMeta($meta);

                return $meta;
            } elseif (!$metaCollection->isEmpty()) {
                // return first when create false
                return $metaCollection->first();
            }
        } else {
            // return exists
            return $metaCollectionFiltered->first();
        }

        return null;
    }
}
