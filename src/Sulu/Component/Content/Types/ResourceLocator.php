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

use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for ResourceLocator.
 */
class ResourceLocator extends SimpleContentType
{
    /**
     * @var string
     */
    private $template;

    public function __construct($template)
    {
        parent::__construct('ResourceLocator', '');
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
