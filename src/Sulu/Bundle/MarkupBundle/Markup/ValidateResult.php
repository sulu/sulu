<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup;

/**
 * Result of markup validate.
 */
class ValidateResult
{
    /**
     * @var bool
     */
    private $valid;

    /**
     * @var string
     */
    private $content;

    /**
     * @param bool $valid
     * @param string $content
     */
    public function __construct($valid, $content)
    {
        $this->valid = $valid;
        $this->content = $content;
    }

    /**
     * Indicates if content is valid or not.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Returns content with marker of invalid tags (data-invalid="true").
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}
