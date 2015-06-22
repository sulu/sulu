<?php

namespace Sulu\Bundle\MediaBundle\Entity;

/**
 * FileVersionMeta.
 */
class FileVersionMeta
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\FileVersion
     */
    private $fileVersion;

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return FileVersionMeta
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return FileVersionMeta
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return FileVersionMeta
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
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
     * Set id.
     *
     * @param int
     *
     * @return int
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set fileVersion.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersion $fileVersion
     *
     * @return FileVersionMeta
     */
    public function setFileVersion(\Sulu\Bundle\MediaBundle\Entity\FileVersion $fileVersion)
    {
        $this->fileVersion = $fileVersion;

        return $this;
    }

    /**
     * Get fileVersion.
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\FileVersion
     */
    public function getFileVersion()
    {
        return $this->fileVersion;
    }

    /**
     * don't clone id.
     */
    public function __clone()
    {
        if ($this->id) {
            $this->setId(null);
        }
    }
}
