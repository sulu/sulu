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
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * Media entity.
 */
class Media implements MediaInterface
{
    use AuditableTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var DoctrineCollection|File[]
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
     * Constructor.
     */
    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function addFile(File $files)
    {
        $this->files[] = $files;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeFile(File $files)
    {
        $this->files->removeElement($files);
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * {@inheritdoc}
     */
    public function getFile($locale, $defaultLocale = null)
    {
        if (1 === count($this->files)) {
            return $this->files[0];
        }

        $defaultFile = $this->files[0];

        foreach ($this->files as $file) {
            foreach ($file->getLatestFileVersion()->getContentLanguages() as $contentLocale) {
                if ($locale === $contentLocale) {
                    return $file;
                }

                if ($defaultLocale === $contentLocale) {
                    $defaultFile = $file;
                }
            }
        }

        return $defaultFile;
    }

    /**
     * {@inheritdoc}
     */
    public function setCollection(CollectionInterface $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function setType(MediaType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function setPreviewImage(self $previewImage = null)
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviewImage()
    {
        return $this->previewImage;
    }
}
