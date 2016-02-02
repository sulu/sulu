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
 * This error represents a expected but not found default template.
 */
class ExpectedDefaultTemplatesNotFound extends WebspaceException
{
    /**
     * ExpectedDefaultTemplatesNotFound constructor.
     *
     * @param string   $webspace
     * @param string[] $expected
     * @param string[] $found
     */
    public function __construct($webspace, $expected, $found)
    {
        parent::__construct(
            sprintf(
                'One of expected types "[%s]" not found (found "[%s]") in webspace "%s".',
                implode(', ', $expected),
                implode(', ', $found),
                $webspace
            )
        );

        $this->webspace = $webspace;
    }
}
