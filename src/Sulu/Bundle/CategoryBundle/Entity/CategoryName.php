<?php

namespace Sulu\Bundle\CategoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CategoryName
 */
class CategoryName
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Sulu\Bundle\CategoryBundle\Entity\Category
     */
    private $category;


    /**
     * Set name
     *
     * @param string $name
     * @return Name
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
     * Set locale
     *
     * @param string $locale
     * @return Name
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    
        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
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
     * Set category
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $category
     * @return Name
     */
    public function setCategory(\Sulu\Bundle\CategoryBundle\Entity\Category $category)
    {
        $this->category = $category;
    
        return $this;
    }

    /**
     * Get category
     *
     * @return \Sulu\Bundle\CategoryBundle\Entity\Category 
     */
    public function getCategory()
    {
        return $this->category;
    }
}