<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

/**
 * Structure for template.
 */
class ErrorStructure extends Structure
{
    protected $exception;

    public function setException(\Exception $exception)
    {
        $this->exception = $exception;
    }

    public function getException()
    {
        return $this->exception;
    }
}
