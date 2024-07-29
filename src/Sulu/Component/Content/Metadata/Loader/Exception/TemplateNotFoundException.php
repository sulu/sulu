<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Loader\Exception;

class TemplateNotFoundException extends \Exception
{
    /**
     * @param string $path
     * @param string $templateKey
     */
    public function __construct(
        private $path,
        private $templateKey,
        ?\Throwable $originalException = null
    ) {
        parent::__construct(\sprintf('a valid template with key "%s" and file "%s" cannot be found', $templateKey, $path), null, $originalException);

        $this->path = $path;
        $this->templateKey = $templateKey;
    }
}
