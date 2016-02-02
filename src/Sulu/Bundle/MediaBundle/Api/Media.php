<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Api;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage;
use Sulu\Bundle\MediaBundle\Entity\Media as Entity;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Media\Exception\FileNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Class Media
 * The Media RestObject is the api entity for the MediaController.
 *
 * @ExclusionPolicy("all")
 */
class Media extends ApiWrapper
{
    /**
     * @var string
     */
    const MEDIA_TYPE_IMAGE = 'image';

    /**
     * @var string
     */
    const MEDIA_TYPE_VIDEO = 'video';

    /**
     * @var string
     */
    const MEDIA_TYPE_AUDIO = 'audio';

    /**
     * @var string
     */
    const MEDIA_TYPE_DOCUMENT = 'document';

    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $formats = [];

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var array
     */
    protected $additionalVersionData = [];

    /**
     * @var FileVersion
     */
    protected $fileVersion = null;

    /**
     * @var File
     */
    protected $file = null;

    public function __construct(Entity $media, $locale, $version = null)
    {
        $this->entity = $media;
        $this->locale = $locale;
        $this->version = $version;
    }

    /**
     * @VirtualProperty
     * @SerializedName("id")
     * @Groups({"partialMedia"})
     *
     * @return int
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("locale")
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param Collection $collection
     *
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
     *
     * @return int
     */
    public function getCollection()
    {
        $collection = $this->entity->getCollection();
        if ($collection) {
            return $collection->getId();
        }

        return;
    }

    /**
     * @param int $size
     *
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
     *
     * @return int
     */
    public function getSize()
    {
        return $this->getFileVersion()->getSize();
    }

    /**
     * @param string $mimeType
     *
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
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->getFileVersion()->getMimeType();
    }

    /**
     * @param string $title
     *
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
     *
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
     *
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
     *
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
     * @param string $copyright
     *
     * @return $this
     */
    public function setCopyright($copyright)
    {
        $this->getMeta(true)->setCopyright($copyright);

        return $this;
    }

    /**
     * Returns copyright for media.
     *
     * @VirtualProperty
     * @SerializedName("copyright")
     *
     * @return string
     *
     * @throws FileVersionNotFoundException
     */
    public function getCopyright()
    {
        $copyright = null;
        /** @var FileVersionMeta $meta */
        foreach ($this->getFileVersion()->getMeta() as $key => $meta) {
            // get description of the meta in locale, when not exists return description of the first meta
            if ($meta->getLocale() == $this->locale || $key == 0) {
                $copyright = $meta->getCopyright();
            }
        }

        return $copyright;
    }

    /**
     * @param int $version
     *
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
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->getFileVersion()->getVersion();
    }

    /**
     * @return array
     */
    public function getAdditionalVersionData()
    {
        return $this->additionalVersionData;
    }

    /**
     * @param array $additionalVersionData
     *
     * @return $this
     */
    public function setAdditionalVersionData($additionalVersionData)
    {
        $this->additionalVersionData = $additionalVersionData;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("versions")
     *
     * @return array
     */
    public function getVersions()
    {
        $versions = [];
        /** @var FileVersion $fileVersion */
        foreach ($this->getFile()->getFileVersions() as $fileVersion) {
            $versionData = [];
            if (isset($this->additionalVersionData[$fileVersion->getVersion()])) {
                $versionData = $this->additionalVersionData[$fileVersion->getVersion()];
            }
            $versionData['version'] = $fileVersion->getVersion();
            $versionData['name'] = $fileVersion->getName();
            $versionData['created'] = $fileVersion->getCreated();
            $versionData['changed'] = $fileVersion->getChanged();
            $versions[$fileVersion->getVersion()] = $versionData;
        }

        return $versions;
    }

    /**
     * @param int $name
     *
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
     *
     * @return int
     */
    public function getName()
    {
        return $this->getFileVersion()->getName();
    }

    /**
     * @VirtualProperty
     * @SerializedName("type")
     *
     * @return MediaType
     */
    public function getType()
    {
        return $this->entity->getType();
    }

    /**
     * @param MediaType $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->entity->setType($type);

        return $this;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isTypeOf($type)
    {
        return $this->getType()->getName() == $type;
    }

    /**
     * @VirtualProperty
     * @SerializedName("isImage")
     *
     * @return bool
     */
    public function isImage()
    {
        return $this->isTypeOf(self::MEDIA_TYPE_IMAGE);
    }

    /**
     * @VirtualProperty
     * @SerializedName("isVideo")
     *
     * @return bool
     */
    public function isVideo()
    {
        return $this->isTypeOf(self::MEDIA_TYPE_VIDEO);
    }

    /**
     * @VirtualProperty
     * @SerializedName("isAudio")
     *
     * @return bool
     */
    public function isAudio()
    {
        return $this->isTypeOf(self::MEDIA_TYPE_AUDIO);
    }

    /**
     * @VirtualProperty
     * @SerializedName("isDocument")
     *
     * @return bool
     */
    public function isDocument()
    {
        return $this->isTypeOf(self::MEDIA_TYPE_DOCUMENT);
    }

    /**
     * @VirtualProperty
     * @SerializedName("storageOptions")
     *
     * @return string
     */
    public function getStorageOptions()
    {
        return $this->getFileVersion()->getStorageOptions();
    }

    /**
     * @param string $storageOptions
     *
     * @return $this
     */
    public function setStorageOptions($storageOptions)
    {
        $this->getFileVersion()->setStorageOptions($storageOptions);

        return $this;
    }

    /**
     * @param array $publishLanguages
     *
     * @return $this
     */
    public function setPublishLanguages($publishLanguages)
    {
        $fileVersion = $this->getFileVersion();

        foreach ($publishLanguages as $locale) {
            $publishLanguage = new FileVersionPublishLanguage();
            $publishLanguage->setFileVersion($fileVersion);
            $publishLanguage->setLocale($locale);
            if (!$fileVersion->getPublishLanguages()->contains($publishLanguage)) {
                $fileVersion->addPublishLanguage($publishLanguage);
            }
        }

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("publishLanguages")
     *
     * @return array
     */
    public function getPublishLanguages()
    {
        $publishLanguages = [];
        /** @var FileVersionPublishLanguage $publishLanguage */
        foreach ($this->getFileVersion()->getPublishLanguages() as $publishLanguage) {
            $publishLanguages[] = $publishLanguage->getLocale();
        }

        return $publishLanguages;
    }

    /**
     * @param array $contentLanguages
     *
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
     *
     * @return array
     */
    public function getContentLanguages()
    {
        $contentLanguages = [];
        /** @var FileVersionContentLanguage $contentLanguage */
        foreach ($this->getFileVersion()->getContentLanguages() as $contentLanguage) {
            $contentLanguages[] = $contentLanguage->getLocale();
        }

        return $contentLanguages;
    }

    /**
     * @return $this
     */
    public function removeTags()
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->removeTags();

        return $this;
    }

    /**
     * @param Tag $tagEntity
     *
     * @return $this
     */
    public function addTag(Tag $tagEntity)
    {
        $fileVersion = $this->getFileVersion();
        if (!$fileVersion->getTags()->contains($tagEntity)) {
            $fileVersion->addTag($tagEntity);
        }

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("tags")
     *
     * @return array
     */
    public function getTags()
    {
        $tags = [];
        foreach ($this->getFileVersion()->getTags() as $tag) {
            /* @var Tag $tag */
            array_push($tags, $tag->getName());
        }

        return $tags;
    }

    /**
     * @SerializedName("thumbnails")
     *
     * @return array
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * @VirtualProperty
     * @SerializedName("thumbnails")
     *
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
     *
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
     * @param \DateTime|string $changed
     *
     * @return $this
     */
    public function setChanged($changed)
    {
        if (is_string($changed)) {
            $changed = new \DateTime($changed);
        }
        $this->getFileVersion()->setChanged($changed);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("changed")
     *
     * @return string
     */
    public function getChanged()
    {
        return $this->getFileVersion()->getChanged();
    }

    /**
     * @param UserInterface $changer
     *
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
     *
     * @return string
     */
    public function getChanger()
    {
        $user = $this->getFileVersion()->getChanger();
        if ($user) {
            return $user->getFullName();
        }

        return;
    }

    /**
     * @VirtualProperty
     * @SerializedName("created")
     *
     * @return mixed
     */
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * @param UserInterface $creator
     *
     * @return $this
     */
    public function setCreator($creator)
    {
        $this->entity->setCreator($creator);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("creator")
     *
     * @return string
     */
    public function getCreator()
    {
        $user = $this->getFileVersion()->getCreator();
        if ($user) {
            return $user->getFullName();
        }

        return;
    }

    /**
     * @param array $properties
     *
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->getFileVersion()->setProperties($properties);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("properties")
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->getFileVersion()->getProperties();
    }

    /**
     * @VirtualProperty
     * @SerializedName("downloadCounter")
     *
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
     * Returns array representation of media.
     *
     * @return array
     */
    public function toArray()
    {
        return [
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
            'downloadCounter' => $this->getDownloadCounter(),
        ];
    }

    /**
     * @return FileVersion
     *
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException
     */
    public function getFileVersion()
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
     * @return File
     *
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\FileNotFoundException
     */
    public function getFile()
    {
        if ($this->file !== null) {
            return $this->file;
        }

        /** @var File $file */
        foreach ($this->entity->getFiles() as $file) {
            // currently only one file per media exists
            $this->file = $file;

            return $this->file;
        }

        throw new FileNotFoundException($this->entity->getId(), $this->version);
    }

    /**
     * @param bool $create
     *
     * @return FileVersionMeta
     */
    private function getMeta($create = false)
    {
        $locale = $this->locale;
        $metaCollection = $this->getFileVersion()->getMeta();

        // get meta only with this locale
        $metaCollectionFiltered = $metaCollection->filter(function ($meta) use ($locale) {
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
            }

            // return first when create false
            return $this->getFileVersion()->getDefaultMeta();
        }

        // return exists
        return $metaCollectionFiltered->first();
    }
}
