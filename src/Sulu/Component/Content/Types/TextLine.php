<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for TextLine
 */
class TextLine extends SimpleContentType
{
    function __construct()
    {
        parent::__construct('TextLine');
    }

    /**
     * returns a template to render a form
     * @return string
     */
    public function getTemplate()
    {
        return 'SuluCoreBundle:content:types/text_line.html.twig';
    }
}
