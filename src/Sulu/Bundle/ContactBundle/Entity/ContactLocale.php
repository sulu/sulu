<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * @ExclusionPolicy("all")
 *
 * ContactLocale.
 */
class ContactLocale
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\ContactInterface
     */
    private $contact;

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return ContactLocale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("locale")
     *
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @VirtualProperty
     * @SerializedName("id")
     *
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set contact.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ContactInterface $contact
     *
     * @return ContactLocale
     */
    public function setContact(\Sulu\Bundle\ContactBundle\Entity\ContactInterface $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\ContactInterface
     */
    public function getContact()
    {
        return $this->contact;
    }
}
