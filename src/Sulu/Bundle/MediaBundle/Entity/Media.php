<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\FileNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * Media.
 *
 * @ExclusionPolicy("all")
 */
class Media implements MediaInterface
{
    use AuditableTrait;

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
     * @var int
     */
    protected $id;

    /**
     * @var DoctrineCollection
     */
    protected $files;

    /**
     * @var CollectionInterface
     * @Exclude
     */
    protected $collection;

    /**
     * @var MediaType
     */
    protected $type;

    /**
     * @var Media
     */
    protected $previewImage;

    /**
     * @var string|null
     */
    protected $currentLocale;

    /**
     * @var int|null
     */
    protected $currentVersion;

    /**
     * @var FileVersionMeta|null
     */
    private $currentLocalizedMeta;

    /**
     * @var FileVersion|null
     */
    protected $currentFileVersion;

    /**
     * @var File|null
     */
    protected $currentFile;

    /**
     * @var string|null
     */
    protected $currentUrl;

    /**
     * @var array|null
     */
    protected $currentFormats;

    /**
     * @var array
     */
    protected $currentAdditionalVersionData = [];

    public function __construct(?string $locale = null, ?int $version = null)
    {
        $this->currentLocale = $locale;
        $this->currentVersion = $version;
        $this->files = new ArrayCollection();
    }

    /**
     * @VirtualProperty
     * @SerializedName("id")
     * @Groups({"partialMedia", "Default"})
     *
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add files.
     *
     * @param File $files
     *
     * @return Media
     */
    public function addFile(File $files)
    {
        $this->files[] = $files;

        return $this;
    }

    /**
     * Remove files.
     *
     * @param File $files
     */
    public function removeFile(File $files)
    {
        $this->files->removeElement($files);
    }

    /**
     * Get files.
     *
     * @return File[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set collection.
     *
     * @param CollectionInterface $collection
     *
     * @return Media
     */
    public function setCollection(CollectionInterface $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get collectionInterface.
     *
     * @return CollectionInterface
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Set type.
     *
     * @param MediaType $type
     *
     * @return Media
     */
    public function setType(MediaType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("type")
     *
     * Get type.
     *
     * @return MediaType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isTypeOf($type): bool
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
     * Set preview image.
     *
     * @param Media $previewImage
     *
     * @return Media
     */
    public function setPreviewImage(self $previewImage = null)
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    /**
     * Get preview image.
     *
     * @return Media
     */
    public function getPreviewImage()
    {
        return $this->previewImage;
    }

    /**
     * @VirtualProperty
     * @SerializedName("locale")
     *
     * @return string
     */
    public function getLocale(): ?string
    {
        return $this->currentLocale;
    }

    public function setLocale(?string $locale): self
    {
        $this->currentLocale = $locale;

        return $this;
    }

    /**
     * @VirtualProperty
     *
     * @return string
     */
    public function getFallbackLocale(): ?string
    {
        if (!$this->getLocalizedMeta()) {
            return null;
        }

        $fallbackLocale = $this->getLocalizedMeta()->getLocale();

        return $fallbackLocale !== $this->currentLocale ? $fallbackLocale : null;
    }

    /**
     * @VirtualProperty
     * @SerializedName("collection")
     *
     * @return int|null
     */
    public function getCollectionId(): ?int
    {
        $collection = $this->getCollection();
        if ($collection) {
            return $collection->getId();
        }

        return null;
    }

    public function setSize(int $size): self
    {
        $this->getFileVersion()->setSize($size);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("size")
     */
    public function getSize(): int
    {
        return $this->getFileVersion()->getSize();
    }

    public function setMimeType(string $mimeType): self
    {
        $this->getFileVersion()->setMimeType($mimeType);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("mimeType")
     */
    public function getMimeType()
    {
        return $this->getFileVersion()->getMimeType();
    }

    public function getExtension(): ?string
    {
        return $this->getFileVersion()->getExtension();
    }

    public function setTitle(string $title): self
    {
        $this->getMeta(true)->setTitle($title);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("title")
     */
    public function getTitle(): ?string
    {
        $currentLocalizedMeta = $this->getLocalizedMeta();

        if (!$currentLocalizedMeta) {
            return null;
        }

        return $currentLocalizedMeta->getTitle();
    }

    public function setDescription(string $description): self
    {
        $this->getMeta(true)->setDescription($description);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("description")
     */
    public function getDescription(): ?string
    {
        $currentLocalizedMeta = $this->getLocalizedMeta();

        if (!$currentLocalizedMeta) {
            return null;
        }

        return $currentLocalizedMeta->getDescription();
    }

    public function setCopyright(string $copyright): self
    {
        $this->getMeta(true)->setCopyright($copyright);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("copyright")
     */
    public function getCopyright(): ?string
    {
        $currentLocalizedMeta = $this->getLocalizedMeta();

        if (!$currentLocalizedMeta) {
            return null;
        }

        return $currentLocalizedMeta->getCopyright();
    }

    public function setCredits(string $credits): self
    {
        $this->getMeta(true)->setCredits($credits);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("credits")
     */
    public function getCredits(): ?string
    {
        $currentLocalizedMeta = $this->getLocalizedMeta();

        if (!$currentLocalizedMeta) {
            return null;
        }

        return $currentLocalizedMeta->getCredits();
    }

    public function setVersion(?int $version): self
    {
        $this->currentVersion = $version;
        $this->currentFileVersion = null;
        $this->currentLocalizedMeta = null;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("version")
     */
    public function getVersion(): int
    {
        if (null === $this->currentVersion) {
            return $this->getFileVersion()->getVersion();
        }

        return $this->currentVersion;
    }

    /**
     * @VirtualProperty
     * @SerializedName("subVersion")
     */
    public function getSubVersion()
    {
        return $this->getFileVersion()->getSubVersion();
    }

    public function getAdditionalVersionData(): array
    {
        return $this->currentAdditionalVersionData;
    }

    public function setAdditionalVersionData(array $currentAdditionalVersionData): self
    {
        $this->currentAdditionalVersionData = $currentAdditionalVersionData;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("versions")
     */
    public function getVersions(): array
    {
        $versions = [];

        $file = $this->getFile();

        /** @var FileVersion $fileVersion */
        foreach ($file->getFileVersions() as $fileVersion) {
            $versionData = [];
            if (isset($this->currentAdditionalVersionData[$fileVersion->getVersion()])) {
                $versionData = $this->currentAdditionalVersionData[$fileVersion->getVersion()];
            }

            $versionData['version'] = $fileVersion->getVersion();
            $versionData['name'] = $fileVersion->getName();
            $versionData['created'] = $fileVersion->getCreated();
            $versionData['changed'] = $fileVersion->getChanged();
            $versionData['active'] = $fileVersion->isActive();
            $versions[$fileVersion->getVersion()] = $versionData;
        }

        return $versions;
    }

    public function setName(string $name): self
    {
        $this->getFileVersion()->setName($name);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("name")
     */
    public function getName(): ?string
    {
        return $this->getFileVersion()->getName();
    }

    /**
     * @VirtualProperty
     * @SerializedName("focusPointX")
     *
     * @return int
     */
    public function getFocusPointX(): ?int
    {
        return $this->getFileVersion()->getFocusPointX();
    }

    public function setFocusPointX($focusPointX): self
    {
        $this->getFileVersion()->setFocusPointX($focusPointX);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("focusPointY")
     */
    public function getFocusPointY(): ?int
    {
        return $this->getFileVersion()->getFocusPointY();
    }

    public function setFocusPointY(int $focusPointY): self
    {
        $this->getFileVersion()->setFocusPointY($focusPointY);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("storageOptions")
     */
    public function getStorageOptions(): array
    {
        return $this->getFileVersion()->getStorageOptions();
    }

    public function setStorageOptions(array $storageOptions): self
    {
        $this->getFileVersion()->setStorageOptions($storageOptions);

        return $this;
    }

    /**
     * @param string[] $publishLanguages
     */
    public function setPublishLanguages(array $publishLanguages): self
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
     * @return string[]
     */
    public function getPublishLanguages(): array
    {
        $publishLanguages = [];
        /** @var FileVersionPublishLanguage $publishLanguage */
        foreach ($this->getFileVersion()->getPublishLanguages() as $publishLanguage) {
            $publishLanguages[] = $publishLanguage->getLocale();
        }

        return $publishLanguages;
    }

    /**
     * @param string[] $contentLanguages
     */
    public function setContentLanguages(array $contentLanguages): self
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
     * @return string[]
     */
    public function getContentLanguages(): array
    {
        $contentLanguages = [];
        /** @var FileVersionContentLanguage $contentLanguage */
        foreach ($this->getFileVersion()->getContentLanguages() as $contentLanguage) {
            $contentLanguages[] = $contentLanguage->getLocale();
        }

        return $contentLanguages;
    }

    public function removeTags(): self
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->removeTags();

        return $this;
    }

    public function addTag(TagInterface $tagEntity): self
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
     * @return string[]
     */
    public function getTagNames()
    {
        $tags = [];
        foreach ($this->getFileVersion()->getTags() as $tag) {
            /* @var TagInterface $tag */
            array_push($tags, $tag->getName());
        }

        return $tags;
    }

    public function addCategory(CategoryInterface $category): self
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->addCategory($category);

        return $this;
    }

    public function removeCategories(): self
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->removeCategories();

        return $this;
    }

    public function addTargetGroup(TargetGroupInterface $targetGroup): self
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->addTargetGroup($targetGroup);

        return $this;
    }

    public function removeTargetGroups(): self
    {
        $fileVersion = $this->getFileVersion();
        $fileVersion->removeTargetGroups();

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("targetGroups")
     * @Groups({"fullMediaAudienceTargeting"})
     *
     * @return int[]
     */
    public function getTargetGroupIds(): array
    {
        if (!$this->getFileVersion()->getTargetGroups()) {
            return [];
        }

        return array_map(function(TargetGroupInterface $targetGroup) {
            return $targetGroup->getId();
        }, $this->getFileVersion()->getTargetGroups()->toArray());
    }

    /**
     * @VirtualProperty
     * @SerializedName("categories")
     *
     * @return int[]
     */
    public function getCategoryIds(): array
    {
        $fileVersion = $this->getFileVersion();
        $categories = $fileVersion->getCategories();

        return array_map(function(CategoryInterface $category) {
            return $category->getId();
        }, $categories->toArray());
    }

    /**
     * @param string[]|null $formats
     */
    public function setFormats(?array $formats): self
    {
        $this->currentFormats = $formats;

        return $this;
    }

    /**
     * @SerializedName("thumbnails")
     *
     * @return string[]
     */
    public function getFormats(): ?array
    {
        return $this->currentFormats;
    }

    /**
     * @VirtualProperty
     * @SerializedName("thumbnails")
     *
     * @return string[]
     */
    public function getThumbnails(): ?array
    {
        return $this->currentFormats;
    }

    /**
     * @VirtualProperty
     * @SerializedName("url")
     *
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->currentUrl;
    }

    public function setUrl(?string $url): self
    {
        $this->currentUrl = $url;

        return $this;
    }

    /**
     * @param string[] $properties
     */
    public function setProperties(array $properties): self
    {
        $this->getFileVersion()->setProperties($properties);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("properties")
     */
    public function getProperties(): array
    {
        return $this->getFileVersion()->getProperties();
    }

    /**
     * @VirtualProperty
     * @SerializedName("downloadCounter")
     */
    public function getDownloadCounter(): int
    {
        $downloadCounter = 0;
        foreach ($this->getFiles() as $file) {
            /** @var FileVersion $fileVersion */
            foreach ($file->getFileVersions() as $fileVersion) {
                $downloadCounter += $fileVersion->getDownloadCounter();
            }
        }

        return $downloadCounter;
    }

    /**
     * @throws FileVersionNotFoundException
     */
    public function getFileVersion(): FileVersion
    {
        if (null !== $this->currentFileVersion) {
            return $this->currentFileVersion;
        }

        $file = $this->getFile();
        $version = $this->currentVersion;

        if (null === $version) {
            $version = $file->getVersion();
        }

        if ($fileVersion = $file->getFileVersion($version)) {
            $this->currentFileVersion = $fileVersion;

            return $fileVersion;
        }

        throw new FileVersionNotFoundException($this->getId(), $this->currentVersion);
    }

    /**
     * @throws FileNotFoundException
     */
    public function getFile(): File
    {
        if (null !== $this->currentFile) {
            return $this->currentFile;
        }

        /** @var File $file */
        foreach ($this->getFiles() as $file) {
            // currently only one file per media exists
            $this->currentFile = $file;

            return $this->currentFile;
        }

        throw new FileNotFoundException($this->getId());
    }

    private function getMeta(bool $create = false): FileVersionMeta
    {
        $locale = $this->getLocale();

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
                $meta->setLocale($locale);
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

    private function getLocalizedMeta(): ?FileVersionMeta
    {
        if ($this->currentLocalizedMeta) {
            return $this->currentLocalizedMeta;
        }

        $locale = $this->currentLocale;
        $metas = $this->getFileVersion()->getMeta();
        $this->currentLocalizedMeta = $metas[0];

        foreach ($metas as $key => $meta) {
            if ($meta->getLocale() == $locale) {
                $this->currentLocalizedMeta = $meta;
                break;
            }
        }

        return $this->currentLocalizedMeta;
    }

    /**
     * @VirtualProperty
     * @SerializedName("creator")
     */
    public function getCreatorName(): ?string
    {
        $user = $this->getFileVersion()->getCreator();
        if ($user) {
            return $user->getFullName();
        }

        return null;
    }

    /**
     * @VirtualProperty
     * @SerializedName("changer")
     */
    public function getChangerName(): ?string
    {
        $user = $this->getFileVersion()->getChanger();
        if ($user) {
            return $user->getFullName();
        }

        return null;
    }

    /**
     * @VirtualProperty
     * @SerializedName("changed")
     */
    public function getCurrentChanged()
    {
        return $this->getFileVersion()->getChanged();
    }

    /**
     * @VirtualProperty
     * @SerializedName("created")
     */
    public function getCreated()
    {
        return $this->getFile()->getCreated();
    }
}
