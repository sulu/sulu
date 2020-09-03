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
    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var string
     */
    private $template;

    public function __construct(Webspace $webspace, string $template)
    {
        parent::__construct(
            \sprintf(
                'The template "%s" is not valid for the webspace "%s". '
                . 'Either it does not exist or was excluded from the webspace.',
                $template,
                $webspace->getKey()
            )
        );

        $this->webspace = $webspace;
        $this->template = $template;
    }

    public function getWebspace()
    {
        return $this->webspace;
    }

    public function getTemplate()
    {
        return $this->template;
    }
}
