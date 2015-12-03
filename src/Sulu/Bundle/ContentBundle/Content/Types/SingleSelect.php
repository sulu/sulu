<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use Sulu\Component\Content\SimpleContentType;

class SingleSelect extends SimpleContentType
{
    /**
     * @var string
     */
    private $template;

    public function __construct($template)
    {
        parent::__construct('SingleSelect', '');
        $this->template = $template;
    }

    /**
     * Returns a template to render a form.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
