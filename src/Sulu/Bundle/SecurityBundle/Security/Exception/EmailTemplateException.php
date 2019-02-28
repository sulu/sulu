<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security\Exception;

/**
 * This Exception is thrown if the email template was not found.
 */
class EmailTemplateException extends SecurityException
{
    /**
     * @var string
     */
    private $template;

    /**
     * EmailTemplateException constructor.
     *
     * @param string $template
     */
    public function __construct($template)
    {
        parent::__construct(sprintf('Email template "%s" does not exist!', $template), 1008);
        $this->template = $template;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'template' => $this->template,
        ];
    }
}
