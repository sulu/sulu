<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Exception;

use Sulu\Component\Webspace\Webspace;

class InvalidErrorTemplateException extends WebspaceException
{
    public function __construct(private string $template, Webspace $webspace)
    {
        parent::__construct(
            \sprintf(
                'Error template "%s" in webspace "%s" has to be defined as default or with a code.',
                $template,
                $webspace->getKey(),
            )
        );

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
