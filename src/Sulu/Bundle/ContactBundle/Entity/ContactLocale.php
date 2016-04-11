<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

/**
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
     * @var \Sulu\Component\Contact\Model\ContactInterface
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
     * Set contact.
     *
     * @param \Sulu\Component\Contact\Model\ContactInterface $contact
     *
     * @return ContactLocale
     */
    public function setContact(\Sulu\Component\Contact\Model\ContactInterface $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return \Sulu\Component\Contact\Model\ContactInterface
     */
    public function getContact()
    {
        return $this->contact;
    }
}
