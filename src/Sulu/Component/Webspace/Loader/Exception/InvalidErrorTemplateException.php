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
 * Represents a wrong error template configuration.
 */
class InvalidErrorTemplateException extends WebspaceException
{
    /**
     * @var string
     */
    private $template;

    /**
     * InvalidErrorTemplateException constructor.
     *
     * @param string $template
     * @param string $webspace
     */
    public function __construct($template, $webspace)
    {
        parent::__construct(
            sprintf(
                'Error template "%s" in webspace "%s" has to be defined as default or with a code.',
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
