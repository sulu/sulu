<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal;

/**
 * The language of a portal
 * @package Sulu\Component\Portal
 */
class Language {
    /**
     * The code of the language
     * @var string
     */
    private $code;

    /**
     * Defines if this language is the main language
     * @var boolean
     */
    private $main;

    /**
     * Defines if this language is the fallback language
     * @var boolean
     */
    private $fallback;

    /**
     * Sets the code of this language
     * @param string $code The code of this language
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Returns the code of this language
     * @return string The code of this language
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Sets if this language is the fallback language
     * @param boolean $fallback
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
    }

    /**
     * Returns true if this language is the fallback
     * @return boolean True if this language is the fallback, otherwise false
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * Sets if this language is the main language
     * @param boolean $main
     */
    public function setMain($main)
    {
        $this->main = $main;
    }

    /**
     * Returns true if this language is the main language
     * @return boolean True if this language is the main language, otherwise false
     */
    public function getMain()
    {
        return $this->main;
    }
}
