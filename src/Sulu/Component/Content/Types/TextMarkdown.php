<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use Sulu\Bundle\ContentBundle\Parser\Parsedown;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for TextMarkdown.
 */
class TextMarkdown extends SimpleContentType
{
    private $template;

    public function __construct($template)
    {
        parent::__construct('TextArea', '');

        $this->template = $template;
    }

    /**
     * returns the converted html content from markdown
     *
     * @param PropertyInterface $property
     *
     * @return string
     */
    public function getContentData(PropertyInterface $property)
    {
        $parsedown = new Parsedown();

        return $parsedown->text(parent::getContentData($property));
    }


    /**
     * returns a template to render a form.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
