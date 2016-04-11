<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Loader\Exception;

/**
 * This error represents a wrong default error template configuration.
 */
class InvalidDefaultErrorTemplateException extends WebspaceException
{
    /**
     * @var string
     */
    private $template;

    /**
     * InvalidErrorTemplateException constructor.
     *
     * @param string $template
     * @param int    $webspace
     */
    public function __construct($template, $webspace)
    {
        parent::__construct(
            sprintf(
                'Default of "%s" in webspace "%s" cannot be false if no code is defined.',
                $template,
                $webspace
            )
        );

        $this->template = $template;
        $this->webspace = $webspace;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
