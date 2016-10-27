<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Import\Exception;

/**
 * Is thrown when an import format was not found.
 */
class WebspaceFormatImporterNotFoundException extends \Exception
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        $message = '',
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct(
            sprintf('Webspace import for "%s" was not found.', $message),
            $code,
            $previous
        );
    }
}
