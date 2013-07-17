<?php

namespace Sulu\Bundle\TranslateBundle\Entity;


/**
 * Code
 */
class Code
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $code;

    /**
     * @var boolean
     */
    private $backend;

    /**
     * @var boolean
     */
    private $frontend;

    /**
     * @var integer
     */
    private $length;

    /**
     * @var \Sulu\Bundle\TranslateBundle\Entity\Package
     */
    private $package;

    /**
     * @var \Sulu\Bundle\TranslateBundle\Entity\Location
     */
    private $location;


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
     * Set code
     *
     * @param string $code
     * @return Code
     */
    public function setCode($code)
    {
        $this->code = $code;
    
        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set backend
     *
     * @param boolean $backend
     * @return Code
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;
    
        return $this;
    }

    /**
     * Get backend
     *
     * @return boolean 
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Set frontend
     *
     * @param boolean $frontend
     * @return Code
     */
    public function setFrontend($frontend)
    {
        $this->frontend = $frontend;
    
        return $this;
    }

    /**
     * Get frontend
     *
     * @return boolean 
     */
    public function getFrontend()
    {
        return $this->frontend;
    }

    /**
     * Set length
     *
     * @param integer $length
     * @return Code
     */
    public function setLength($length)
    {
        $this->length = $length;
    
        return $this;
    }

    /**
     * Get length
     *
     * @return integer 
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set package
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Package $package
     * @return Code
     */
    public function setPackage(\Sulu\Bundle\TranslateBundle\Entity\Package $package = null)
    {
        $this->package = $package;
    
        return $this;
    }

    /**
     * Get package
     *
     * @return \Sulu\Bundle\TranslateBundle\Entity\Package 
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set location
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Location $location
     * @return Code
     */
    public function setLocation(\Sulu\Bundle\TranslateBundle\Entity\Location $location = null)
    {
        $this->location = $location;
    
        return $this;
    }

    /**
     * Get location
     *
     * @return \Sulu\Bundle\TranslateBundle\Entity\Location 
     */
    public function getLocation()
    {
        return $this->location;
    }
}