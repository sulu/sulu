<?php

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccountCategory
 */
class AccountCategory
{
    /**
     * @var string
     */
    private $category;

    /**
     * @var integer
     */
    private $id;

    /**
     * Set category
     *
     * @param string $category
     * @return AccountCategory
     */
    public function setCategory($category)
    {
        $this->category = $category;
    
        return $this;
    }

    /**
     * Get category
     *
     * @return string 
     */
    public function getCategory()
    {
        return $this->category;
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
}