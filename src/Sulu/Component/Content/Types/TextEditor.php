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

use Sulu\Component\Content\PropertyParameter;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for TextEditor.
 */
class TextEditor extends SimpleContentType
{
    private $template;

    public function __construct($template)
    {
        parent::__construct('TextEditor', '');

        $this->template = $template;
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

    /**
     * returns default parameters.
     *
     * @return array
     */
    public function getDefaultParams()
    {
        return array(
            'tables' => new PropertyParameter('tables', true),
            'links' => new PropertyParameter('links', true),
            'scripts' => new PropertyParameter('scripts', true),
            'iframes' => new PropertyParameter('iframes', true),
            'height' => new PropertyParameter('height', 300),
            'maxHeight' => new PropertyParameter('maxHeight', 500),
            'enterMode' => new PropertyParameter('enterMode', null),
            'pasteFromWord' => new PropertyParameter('pasteFromWord', true),
        );
    }
}
