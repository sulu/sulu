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
 * indicates an exception in template loading.
 */
class TemplateNotFoundException extends \Exception
{
    /**
     * @param string $path
     * @param string $templateKey
     * @param ?\Throwable $originalException
     */
    public function __construct(private $path, private $templateKey, $originalException = null)
    {
        parent::__construct(\sprintf(
            'a valid template with key "%s" and file "%s" cannot be found', $this->templateKey, $this->path),
            null,
            $originalException
        );
    }
}
