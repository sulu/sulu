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
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Symfony\Component\Mime\MimeTypes;

/**
 * @phpstan-import-type StorageOptions from StorageInterface
 */
class FileVersion implements AuditableInterface
{
    use AuditableTrait;

    private int $id;

    private string $name;

    private int $version;

    private int $subVersion = 0;

    private int $size;

    private ?string $mimeType = null;

    private ?string $storageOptions = null;

    private int $downloadCounter = 0;

    /** @var DoctrineCollection<int, FileVersionMeta> */
    private DoctrineCollection $meta;

    /** @var DoctrineCollection<string, FormatOptions> */
    private DoctrineCollection $formatOptions;

    #[Exclude]
    private ?File $file = null;

    /** @var DoctrineCollection<int, TagInterface> */
    private DoctrineCollection $tags;

    private ?FileVersionMeta $defaultMeta = null;

    private ?string $properties = '{}';

    /** @var DoctrineCollection<int, CategoryInterface> */
    private DoctrineCollection $categories;

    /** @var DoctrineCollection<int, TargetGroupInterface> */
    private DoctrineCollection $targetGroups;

    private ?int $focusPointX = null;

    private ?int $focusPointY = null;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->formatOptions = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->targetGroups = new ArrayCollection();
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function increaseSubVersion(): static
    {
        ++$this->subVersion;

        return $this;
    }

    public function getSubVersion(): int
    {
        return $this->subVersion;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getExtension(): ?string
    {
        $pathInfo = \pathinfo($this->getName());
        $extension = MimeTypes::getDefault()->getExtensions($this->getMimeType() ?? '')[0] ?? null;
        if ($extension) {
            return $extension;
        } elseif (isset($pathInfo['extension'])) {
            return $pathInfo['extension'];
        }

        return null;
    }

    /**
     * @param StorageOptions $storageOptions
     */
    public function setStorageOptions(array $storageOptions): static
    {
        $serializedText = \json_encode($storageOptions);
        if (false === $serializedText) {
            return $this;
        }

        $this->storageOptions = $serializedText;

        return $this;
    }

    /**
     * @return StorageOptions
     */
    public function getStorageOptions(): array
    {
        /** @var StorageOptions|null $storageOptions */
        $storageOptions = \json_decode($this->storageOptions ?? '', true);
        if (!$storageOptions) {
            return [];
        }

        return $storageOptions;
    }

    public function setDownloadCounter(int $downloadCounter): static
    {
        $this->downloadCounter = $downloadCounter;

        return $this;
    }

    public function getDownloadCounter(): int
    {
        return $this->downloadCounter;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function addMeta(FileVersionMeta $meta): static
    {
        $this->meta[] = $meta;

        return $this;
    }

    public function removeMeta(FileVersionMeta $meta): static
    {
        $this->meta->removeElement($meta);

        return $this;
    }

    /**
     * @return DoctrineCollection<int, FileVersionMeta>
     */
    public function getMeta(): DoctrineCollection
    {
        return $this->meta;
    }

    public function addFormatOptions(FormatOptions $formatOptions): static
    {
        $this->formatOptions[$formatOptions->getFormatKey()] = $formatOptions;

        return $this;
    }

    /**
     * @return DoctrineCollection<string, FormatOptions>
     */
    public function getFormatOptions(): DoctrineCollection
    {
        return $this->formatOptions;
    }

    public function setFile(?File $file = null): static
    {
        $this->file = $file;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function addTag(TagInterface $tags): static
    {
        $this->tags[] = $tags;

        return $this;
    }

    public function removeTag(TagInterface $tags): static
    {
        $this->tags->removeElement($tags);

        return $this;
    }

    public function removeTags(): static
    {
        foreach ($this->tags as $tag) {
            $this->tags->removeElement($tag);
        }

        return $this;
    }

    /**
     * @return DoctrineCollection<int, TagInterface>
     */
    public function getTags(): DoctrineCollection
    {
        return $this->tags;
    }

    public function setDefaultMeta(?FileVersionMeta $defaultMeta = null): static
    {
        $this->defaultMeta = $defaultMeta;

        return $this;
    }

    public function getDefaultMeta(): ?FileVersionMeta
    {
        return $this->defaultMeta;
    }

    /**
     * don't clone id to create a new entities.
     */
    public function __clone()
    {
        if (isset($this->id)) {
            unset($this->id);
            /** @var FileVersionMeta[] $newMetaList */
            $newMetaList = [];
            $defaultMetaLocale = $this->getDefaultMeta()?->getLocale();
            /** @var FormatOptions[] $newFormatOptionsArray */
            $newFormatOptionsArray = [];

            foreach ($this->meta as $meta) {
                /* @var FileVersionMeta $meta */
                $newMetaList[] = clone $meta;
            }

            $this->meta->clear(); // @phpstan-ignore-line disallowed.method it saved to call here as it a none persisted entity
            foreach ($newMetaList as $newMeta) {
                $newMeta->setFileVersion($this);
                $this->addMeta($newMeta);

                if ($newMeta->getLocale() === $defaultMetaLocale) {
                    $this->setDefaultMeta($newMeta);
                }
            }

            foreach ($this->formatOptions as $formatOptions) {
                /* @var FormatOptions $formatOptions */
                $newFormatOptionsArray[] = clone $formatOptions;
            }

            $this->formatOptions->clear(); // @phpstan-ignore-line disallowed.method it saved to call here as it a none persisted entity
            foreach ($newFormatOptionsArray as $newFormatOptions) {
                $newFormatOptions->setFileVersion($this);
                $this->addFormatOptions($newFormatOptions);
            }
        }
    }

    public function isActive(): bool
    {
        return $this->file && $this->version === $this->file->getVersion();
    }

    public function getProperties(): mixed
    {
        return \json_decode($this->properties ?? '', true);
    }

    public function setProperties(array $properties): static
    {
        $serializedText = \json_encode($properties);
        if (false === $serializedText) {
            return $this;
        }
        $this->properties = $serializedText;

        return $this;
    }

    public function addCategory(CategoryInterface $categories): static
    {
        $this->categories[] = $categories;

        return $this;
    }

    public function removeCategories(): static
    {
        foreach ($this->categories as $category) {
            $this->categories->removeElement($category);
        }

        return $this;
    }

    /**
     * @return DoctrineCollection<int, CategoryInterface>
     */
    public function getCategories(): DoctrineCollection
    {
        return $this->categories;
    }

    public function addTargetGroup(TargetGroupInterface $targetGroup): static
    {
        $this->targetGroups[] = $targetGroup;

        return $this;
    }

    public function removeTargetGroups(): static
    {
        foreach ($this->targetGroups as $targetGroup) {
            $this->targetGroups->removeElement($targetGroup);
        }

        return $this;
    }

    /**
     * @return DoctrineCollection<int, TargetGroupInterface>
     */
    public function getTargetGroups(): DoctrineCollection
    {
        return $this->targetGroups;
    }

    public function getFocusPointX(): ?int
    {
        return $this->focusPointX;
    }

    public function setFocusPointX(?int $focusPointX): static
    {
        $this->focusPointX = $focusPointX;

        return $this;
    }

    public function getFocusPointY(): ?int
    {
        return $this->focusPointY;
    }

    public function setFocusPointY(?int $focusPointY): static
    {
        $this->focusPointY = $focusPointY;

        return $this;
    }
}
