<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

/**
 * This Exception is thrown when a format-options is encountered, which misses
 * one or more parameters.
 */
class FormatOptionsMissingParameterException extends MediaException
{
    public function __construct()
    {
        parent::__construct(
            'Format options object misses a required parameter',
            self::EXCEPTION_FORMAT_OPTIONS_MISSING_PARAMETER
        );
    }
}
