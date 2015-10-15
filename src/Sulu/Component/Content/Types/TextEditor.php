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

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for TextEditor.
 */
class TextEditor extends SimpleContentType
{
    /**
     * @var string
     */
    private $template;

    public function __construct($template)
    {
        parent::__construct('TextEditor', '');

        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [
            'table' => new PropertyParameter('table', true),
            'link' => new PropertyParameter('link', true),
            'height' => new PropertyParameter('height', 65),
            'max_height' => new PropertyParameter('max_height', 300),
            'paste_from_word' => new PropertyParameter('paste_from_word', true),
        ];
    }
}
