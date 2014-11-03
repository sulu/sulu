<?php

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * TermsOfPayment
 */
class TermsOfPayment
{
    /**
     * @var string
     * @Groups({"fullAccount"})
     */
    private $terms;

    /**
     * @var integer
     * @Groups({"fullAccount"})
     */
    private $id;


    /**
     * Set terms
     *
     * @param string $terms
     * @return TermsOfPayment
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;
    
        return $this;
    }

    /**
     * Get terms
     *
     * @return string 
     */
    public function getTerms()
    {
        return $this->terms;
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
