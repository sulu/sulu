<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FileVersion
 */
class FileVersion
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $version;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $fileVersionContentLanguages;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $fileVersionPublishLanguages;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\File
     */
    private $file;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\User
     */
    private $changer;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\User
     */
    private $creator;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fileVersionContentLanguages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->fileVersionPublishLanguages = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set name
     *
     * @param string $name
     * @return FileVersion
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return FileVersion
     */
    public function setVersion($version)
    {
        $this->version = $version;
    
        return $this;
    }

    /**
     * Get version
     *
     * @return integer 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return FileVersion
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return FileVersion
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    
        return $this;
    }

    /**
     * Get changed
     *
     * @return \DateTime 
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add fileVersionContentLanguages
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage $fileVersionContentLanguages
     * @return FileVersion
     */
    public function addFileVersionContentLanguage(\Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage $fileVersionContentLanguages)
    {
        $this->fileVersionContentLanguages[] = $fileVersionContentLanguages;
    
        return $this;
    }

    /**
     * Remove fileVersionContentLanguages
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage $fileVersionContentLanguages
     */
    public function removeFileVersionContentLanguage(\Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage $fileVersionContentLanguages)
    {
        $this->fileVersionContentLanguages->removeElement($fileVersionContentLanguages);
    }

    /**
     * Get fileVersionContentLanguages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getFileVersionContentLanguages()
    {
        return $this->fileVersionContentLanguages;
    }

    /**
     * Add fileVersionPublishLanguages
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage $fileVersionPublishLanguages
     * @return FileVersion
     */
    public function addFileVersionPublishLanguage(\Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage $fileVersionPublishLanguages)
    {
        $this->fileVersionPublishLanguages[] = $fileVersionPublishLanguages;
    
        return $this;
    }

    /**
     * Remove fileVersionPublishLanguages
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage $fileVersionPublishLanguages
     */
    public function removeFileVersionPublishLanguage(\Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage $fileVersionPublishLanguages)
    {
        $this->fileVersionPublishLanguages->removeElement($fileVersionPublishLanguages);
    }

    /**
     * Get fileVersionPublishLanguages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getFileVersionPublishLanguages()
    {
        return $this->fileVersionPublishLanguages;
    }

    /**
     * Set file
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\File $file
     * @return FileVersion
     */
    public function setFile(\Sulu\Bundle\MediaBundle\Entity\File $file = null)
    {
        $this->file = $file;
    
        return $this;
    }

    /**
     * Get file
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\File 
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set changer
     *
     * @param \Sulu\Component\Security\UserInterface $changer
     * @return FileVersion
     */
    public function setChanger(\Sulu\Component\Security\UserInterface $changer = null)
    {
        $this->changer = $changer;
    
        return $this;
    }

    /**
     * Get changer
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator
     *
     * @param \Sulu\Component\Security\UserInterface $creator
     * @return FileVersion
     */
    public function setCreator(\Sulu\Component\Security\UserInterface $creator = null)
    {
        $this->creator = $creator;
    
        return $this;
    }

    /**
     * Get creator
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }
}
