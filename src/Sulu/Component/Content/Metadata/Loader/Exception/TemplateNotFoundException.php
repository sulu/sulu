<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Loader\Exception;

use Exception;

/**
 * indicates an exception in template loading.
 */
class TemplateNotFoundException extends Exception
{
    /**
     * @var string
     */
    private $templateKey;

    /**
     * @var string
     */
    private $path;

    public function __construct($path, $templateKey, $originalException = null)
    {
        parent::__construct(sprintf('a valid template with key "%s" and file "%s" cannot be found', $templateKey, $path), null, $originalException);

        $this->path = $path;
        $this->templateKey = $templateKey;
    }
}
