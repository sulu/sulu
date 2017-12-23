<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * FileVersion.
 */
class FileVersion implements AuditableInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $version;

    /**
     * @var int
     */
    private $subVersion = 0;

    /**
     * @var int
     */
    private $size;

    /**
     * @var string
     */
    private $mimeType;

    /**
     * @var string
     */
    private $storageOptions;

    /**
     * @var string
     */
    private $storageType;

    /**
     * @var int
     */
    private $downloadCounter = 0;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var int
     */
    private $id;

    /**
     * @var DoctrineCollection|FileVersionContentLanguage[]
     */
    private $contentLanguages = [];

    /**
     * @var DoctrineCollection|FileVersionPublishLanguage[]
     */
    private $publishLanguages = [];

    /**
     * @var DoctrineCollection|FileVersionMeta[]
     */
    private $meta = [];

    /**
     * @var DoctrineCollection|FormatOptions[]
     */
    private $formatOptions = [];

    /**
     * @var File
     * @Exclude
     */
    private $file;

    /**
     * @var DoctrineCollection|TagInterface[]
     */
    private $tags = [];

    /**
     * @var UserInterface
     */
    private $changer;

    /**
     * @var UserInterface
     */
    private $creator;

    /**
     * @var FileVersionMeta
     */
    private $defaultMeta;

    /**
     * @var string
     */
    private $properties = '{}';

    /**
     * @var DoctrineCollection|CategoryInterface[]
     */
    private $categories = [];

    /**
     * @var DoctrineCollection|TargetGroupInterface[]
     */
    private $targetGroups;

    /**
     * @var int
     */
    private $focusPointX;

    /**
     * @var int
     */
    private $focusPointY;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->contentLanguages = new ArrayCollection();
        $this->publishLanguages = new ArrayCollection();
        $this->meta = new ArrayCollection();
        $this->formatOptions = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->targetGroups = new ArrayCollection();
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return FileVersion
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set version.
     *
     * @param int $version
     *
     * @return FileVersion
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Increases the subversion. Required for cache busting on certain operations which change the image without
     * creating a new file version.
     *
     * @return FileVersion
     */
    public function increaseSubVersion()
    {
        ++$this->subVersion;

        return $this;
    }

    /**
     * Get subVersion.
     *
     * @return int
     */
    public function getSubVersion()
    {
        return $this->subVersion;
    }

    /**
     * Set size.
     *
     * @param int $size
     *
     * @return FileVersion
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set mimeType.
     *
     * @param string $mimeType
     *
     * @return FileVersion
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get mimeType.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set storageOptions.
     *
     * @param string $storageOptions
     *
     * @return FileVersion
     */
    public function setStorageOptions($storageOptions)
    {
        $this->storageOptions = $storageOptions;

        return $this;
    }

    /**
     * Get storageOptions.
     *
     * @return string
     */
    public function getStorageOptions()
    {
        return $this->storageOptions;
    }

    /**
     * Set downloadCounter.
     *
     * @param int $downloadCounter
     *
     * @return FileVersion
     */
    public function setDownloadCounter($downloadCounter)
    {
        $this->downloadCounter = $downloadCounter;

        return $this;
    }

    /**
     * Get downloadCounter.
     *
     * @return int
     */
    public function getDownloadCounter()
    {
        return $this->downloadCounter;
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get created.
     *
     * @param \DateTime $created
     *
     * @return $this
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Set changed.
     *
     * @param \DateTime $changed
     *
     * @return $this
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add contentLanguages.
     *
     * @param FileVersionContentLanguage $contentLanguages
     *
     * @return FileVersion
     */
    public function addContentLanguage(FileVersionContentLanguage $contentLanguages)
    {
        $this->contentLanguages[] = $contentLanguages;

        return $this;
    }

    /**
     * Remove contentLanguages.
     *
     * @param FileVersionContentLanguage $contentLanguages
     */
    public function removeContentLanguage(FileVersionContentLanguage $contentLanguages)
    {
        $this->contentLanguages->removeElement($contentLanguages);
    }

    /**
     * Has content language.
     *
     * @param string $locale
     *
     * @return bool
     */
    public function hasContentLanguage($locale)
    {
        foreach ($this->contentLanguages as $contentLanguage) {
            if ($locale === $contentLanguage->getLocale()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get contentLanguages.
     *
     * @return DoctrineCollection|FileVersionContentLanguage[]
     */
    public function getContentLanguages()
    {
        return $this->contentLanguages;
    }

    /**
     * Add publishLanguages.
     *
     * @param FileVersionPublishLanguage $publishLanguages
     *
     * @return FileVersion
     */
    public function addPublishLanguage(FileVersionPublishLanguage $publishLanguages)
    {
        $this->publishLanguages[] = $publishLanguages;

        return $this;
    }

    /**
     * Remove publishLanguages.
     *
     * @param FileVersionPublishLanguage $publishLanguages
     */
    public function removePublishLanguage(FileVersionPublishLanguage $publishLanguages)
    {
        $this->publishLanguages->removeElement($publishLanguages);
    }

    /**
     * Get publishLanguages.
     *
     * @return DoctrineCollection|FileVersionPublishLanguage[]
     */
    public function getPublishLanguages()
    {
        return $this->publishLanguages;
    }

    /**
     * Add meta.
     *
     * @param FileVersionMeta $meta
     *
     * @return FileVersion
     */
    public function addMeta(FileVersionMeta $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * Remove meta.
     *
     * @param FileVersionMeta $meta
     */
    public function removeMeta(FileVersionMeta $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * Get meta.
     *
     * @return FileVersionMeta[]
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Get meta by locale.
     *
     * @param string $locale
     * @param bool $default
     *
     * @return FileVersionMeta|null
     */
    public function getMetaByLocale($locale, $default = false)
    {
        foreach ($this->meta as $meta) {
            if ($locale === $meta->getLocale()) {
                return $meta;
            }
        }

        if ($default) {
            return $this->defaultMeta;
        }

        return null;
    }

    /**
     * Adds a format-options entity to the file-version.
     *
     * @param FormatOptions $formatOptions
     *
     * @return FileVersion
     */
    public function addFormatOptions(FormatOptions $formatOptions)
    {
        $this->formatOptions[$formatOptions->getFormatKey()] = $formatOptions;

        return $this;
    }

    /**
     * Get formatOptions.
     *
     * @return DoctrineCollection|FormatOptions[]
     */
    public function getFormatOptions()
    {
        return $this->formatOptions;
    }

    /**
     * Set file.
     *
     * @param File $file
     *
     * @return FileVersion
     */
    public function setFile(File $file = null)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file.
     *
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Add tags.
     *
     * @param TagInterface $tags
     *
     * @return FileVersion
     */
    public function addTag(TagInterface $tags)
    {
        $this->tags[] = $tags;

        return $this;
    }

    /**
     * Remove tags.
     *
     * @param TagInterface $tags
     */
    public function removeTag(TagInterface $tags)
    {
        $this->tags->removeElement($tags);
    }

    /**
     * Remove all tags.
     */
    public function removeTags()
    {
        $this->tags->clear();
    }

    /**
     * Get tags.
     *
     * @return DoctrineCollection|TagInterface[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return FileVersion
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return FileVersion
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set defaultMeta.
     *
     * @param FileVersionMeta $defaultMeta
     *
     * @return FileVersion
     */
    public function setDefaultMeta(FileVersionMeta $defaultMeta = null)
    {
        $this->defaultMeta = $defaultMeta;

        return $this;
    }

    /**
     * Get defaultMeta.
     *
     * @return FileVersionMeta
     */
    public function getDefaultMeta()
    {
        return $this->defaultMeta;
    }

    /**
     * don't clone id to create a new entities.
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            /** @var FileVersionMeta[] $newMetaList */
            $newMetaList = [];
            $defaultMetaLocale = $this->getDefaultMeta()->getLocale();
            /** @var FileVersionContentLanguage[] $newContentLanguageList */
            $newContentLanguageList = [];
            /** @var FileVersionPublishLanguage[] $newPublishLanguageList */
            $newPublishLanguageList = [];
            /** @var FormatOptions[] $newFormatOptionsArray */
            $newFormatOptionsArray = [];

            foreach ($this->meta as $meta) {
                /** @var FileVersionMeta $meta */
                $newMetaList[] = clone $meta;
            }

            $this->meta->clear();
            foreach ($newMetaList as $newMeta) {
                $newMeta->setFileVersion($this);
                $this->addMeta($newMeta);

                if ($newMeta->getLocale() === $defaultMetaLocale) {
                    $this->setDefaultMeta($newMeta);
                }
            }

            foreach ($this->contentLanguages as $contentLanguage) {
                /** @var FileVersionContentLanguage $contentLanguage */
                $newContentLanguageList[] = clone $contentLanguage;
            }

            $this->contentLanguages->clear();
            foreach ($newContentLanguageList as $newContentLanguage) {
                $newContentLanguage->setFileVersion($this);
                $this->addContentLanguage($newContentLanguage);
            }

            foreach ($this->publishLanguages as $publishLanguage) {
                /** @var FileVersionPublishLanguage $publishLanguage */
                $newPublishLanguageList[] = clone $publishLanguage;
            }

            $this->publishLanguages->clear();
            foreach ($newPublishLanguageList as $newPublishLanguage) {
                $newPublishLanguage->setFileVersion($this);
                $this->addPublishLanguage($newPublishLanguage);
            }

            foreach ($this->formatOptions as $formatOptions) {
                /** @var FormatOptions $formatOptions */
                $newFormatOptionsArray[] = clone $formatOptions;
            }

            $this->formatOptions->clear();
            foreach ($newFormatOptionsArray as $newFormatOptions) {
                /** @var FormatOptions $newFormatOptions */
                $newFormatOptions->setFileVersion($this);
                $this->addFormatOptions($newFormatOptions);
            }
        }
    }

    /**
     * Set storageType.
     *
     * @param string $storageType
     *
     * @return FileVersion
     */
    public function setStorageType($storageType)
    {
        $this->storageType = $storageType;

        return $this;
    }

    /**
     * Get storageType.
     *
     * @return string
     */
    public function getStorageType()
    {
        return $this->storageType;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return json_decode($this->properties, true);
    }

    /**
     * @param array $properties
     *
     * @return self
     */
    public function setProperties(array $properties)
    {
        $this->properties = json_encode($properties);

        return $this;
    }

    /**
     * Add categories.
     *
     * @param CategoryInterface $categories
     *
     * @return self
     */
    public function addCategory(CategoryInterface $categories)
    {
        $this->categories[] = $categories;

        return $this;
    }

    /**
     * Remove categories.
     */
    public function removeCategories()
    {
        $this->categories->clear();
    }

    /**
     * Get categories.
     *
     * @return DoctrineCollection|CategoryInterface[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Add a target group.
     *
     * @param TargetGroupInterface $targetGroup
     */
    public function addTargetGroup(TargetGroupInterface $targetGroup)
    {
        $this->targetGroups[] = $targetGroup;
    }

    /**
     * Remove all target groups.
     */
    public function removeTargetGroups()
    {
        $this->targetGroups->clear();
    }

    /**
     * @return DoctrineCollection|TargetGroupInterface[]
     */
    public function getTargetGroups()
    {
        return $this->targetGroups;
    }

    /**
     * Returns the x coordinate of the focus point.
     *
     * @return int
     */
    public function getFocusPointX()
    {
        return $this->focusPointX;
    }

    /**
     * Sets the x coordinate of the focus point.
     *
     * @param int $focusPointX
     */
    public function setFocusPointX($focusPointX)
    {
        $this->focusPointX = $focusPointX;
    }

    /**
     * Returns the y coordinate of the focus point.
     *
     * @return int
     */
    public function getFocusPointY()
    {
        return $this->focusPointY;
    }

    /**
     * Sets the y coordinate of the focus point.
     *
     * @param int $focusPointY
     */
    public function setFocusPointY($focusPointY)
    {
        $this->focusPointY = $focusPointY;
    }
}
