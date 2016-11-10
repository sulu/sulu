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
use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface as CategoryEntity;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
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
     * @var FileVersionMeta
     */
    protected $localizedMeta = null;

    /**
     * @var File
     */
    protected $file = null;

    public function __construct(MediaInterface $media, $locale, $version = null)
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
     * @VirtualProperty
     *
     * @return string
     */
    public function getFallbackLocale()
    {
        if (!$this->getLocalizedMeta()) {
            return;
        }

        $fallbackLocale = $this->getLocalizedMeta()->getLocale();

        return $fallbackLocale !== $this->locale ? $fallbackLocale : null;
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
        if (!$this->getLocalizedMeta()) {
            return;
        }

        return $this->getLocalizedMeta()->getTitle();
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
        if (!$this->getLocalizedMeta()) {
            return;
        }

        return $this->getLocalizedMeta()->getDescription();
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
        if (!$this->getLocalizedMeta()) {
            return;
        }

        return $this->getLocalizedMeta()->getCopyright();
    }

    /**
     * @param string $credits
     *
     * @return $this
     */
    public function setCredits($credits)
    {
        $this->getMeta(true)->setCredits($credits);

        return $this;
    }

    /**
     * Returns copyright for media.
     *
     * @VirtualProperty
     * @SerializedName("credits")
     *
     * @return string
     *
     * @throws FileVersionNotFoundException
     */
    public function getCredits()
    {
        if (!$this->getLocalizedMeta()) {
            return;
        }

        return $this->getLocalizedMeta()->getCredits();
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
     * @VirtualProperty
     * @SerializedName("subVersion")
     *
     * @return int
     */
    public function getSubVersion()
    {
        return $this->getFileVersion()->getSubVersion();
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

    /**
     * Searches the meta for the file version in the media locale. Might also return a fallback.
     *
     * @return FileVersionMeta
     *
     * @throws FileVersionNotFoundException
     */
    private function getLocalizedMeta()
    {
        if ($this->localizedMeta) {
            return $this->localizedMeta;
        }

        $metas = $this->getFileVersion()->getMeta();
        $this->localizedMeta = $metas[0];

        foreach ($metas as $key => $meta) {
            if ($meta->getLocale() == $this->locale) {
                $this->localizedMeta = $meta;
                break;
            }
        }

        return $this->localizedMeta;
    }

    /**
     * Adds a category to the entity.
     *
     * @param CategoryEntity $category
     */
    public function addCategory(CategoryEntity $category)
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->addCategory($category);

        return $this;
    }

    /**
     * Removes all category from the entity.
     */
    public function removeCategories()
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->removeCategories();
    }

    /**
     * Returns the categories of the media.
     *
     * @VirtualProperty
     * @SerializedName("categories")
     *
     * @return Category[]
     */
    public function getCategories()
    {
        $apiCategories = [];
        $fileVersion = $this->getFileVersion();
        $categories = $fileVersion->getCategories();

        // return Category API item
        if (count($categories)) {
            foreach ($categories as $category) {
                $apiCategories[] = new Category($category, $this->locale);
            }
        }

        return $apiCategories;
    }

    /**
     * Returns the x coordinate of the focus point.
     *
     * @VirtualProperty
     * @SerializedName("focusPointX")
     *
     * @return int
     */
    public function getFocusPointX()
    {
        return $this->getFileVersion()->getFocusPointX();
    }

    /**
     * Sets the x coordinate of the focus point.
     *
     * @param int $focusPointX
     */
    public function setFocusPointX($focusPointX)
    {
        $this->getFileVersion()->setFocusPointX($focusPointX);
    }

    /**
     * Returns the y coordinate of the focus point.
     *
     * @VirtualProperty
     * @SerializedName("focusPointY")
     *
     * @return int
     */
    public function getFocusPointY()
    {
        return $this->getFileVersion()->getFocusPointY();
    }

    /**
     * Sets the y coordinate of the focus point.
     *
     * @param int $focusPointY
     */
    public function setFocusPointY($focusPointY)
    {
        $this->getFileVersion()->setFocusPointY($focusPointY);
    }
}
