<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Entity;

/**
 * FilterTranslation.
 */
class FilterTranslation
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
     * @var string
     */
    private $shortDescription;

    /**
     * @var string
     */
    private $longDescription;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ResourceBundle\Entity\Filter
     */
    private $filter;

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return FilterTranslation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return FilterTranslation
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
     * Set shortDescription.
     *
     * @param string $shortDescription
     *
     * @return FilterTranslation
     */
    public function setShortDescription($shortDescription)
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    /**
     * Get shortDescription.
     *
     * @return string
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * Set longDescription.
     *
     * @param string $longDescription
     *
     * @return FilterTranslation
     */
    public function setLongDescription($longDescription)
    {
        $this->longDescription = $longDescription;

        return $this;
    }

    /**
     * Get longDescription.
     *
     * @return string
     */
    public function getLongDescription()
    {
        return $this->longDescription;
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
     * Set filter.
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\Filter $filter
     *
     * @return FilterTranslation
     */
    public function setFilter(\Sulu\Bundle\ResourceBundle\Entity\Filter $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get filter.
     *
     * @return \Sulu\Bundle\ResourceBundle\Entity\Filter
     */
    public function getFilter()
    {
        return $this->filter;
    }
}
