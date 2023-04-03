<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Template\Exception;

/**
 * Thrown when there is an error concerning a template.
 */
class TemplateException extends \Exception
{
    /**
     * @param string $template The template causing the error
     * @param string $message
     */
    public function __construct(protected $template, $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Returns the template causing the error.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
