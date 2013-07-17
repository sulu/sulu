<?php

namespace Sulu\Bundle\TranslateBundle\Entity;


/**
 * Location
 */
class Location
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $codes;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->codes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return Location
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
     * Add codes
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Code $codes
     * @return Location
     */
    public function addCode(\Sulu\Bundle\TranslateBundle\Entity\Code $codes)
    {
        $this->codes[] = $codes;
    
        return $this;
    }

    /**
     * Remove codes
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Code $codes
     */
    public function removeCode(\Sulu\Bundle\TranslateBundle\Entity\Code $codes)
    {
        $this->codes->removeElement($codes);
    }

    /**
     * Get codes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCodes()
    {
        return $this->codes;
    }
}