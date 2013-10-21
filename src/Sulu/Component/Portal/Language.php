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
}
