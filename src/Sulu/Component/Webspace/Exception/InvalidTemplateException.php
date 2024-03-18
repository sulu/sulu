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

class InvalidTemplateException extends \Exception
{
    public function __construct(private Webspace $webspace, private string $template)
    {
        parent::__construct(
            \sprintf(
                'The template "%s" is not valid for the webspace "%s". '
                . 'Either it does not exist or was excluded from the webspace.',
                $template,
                $webspace->getKey()
            )
        );
    }

    public function getWebspace(): Webspace
    {
        return $this->webspace;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
