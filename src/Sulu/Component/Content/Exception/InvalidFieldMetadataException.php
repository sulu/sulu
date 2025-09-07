<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

abstract class InvalidFieldMetadataException extends \Exception
{
    /**
     * @var string
     */
    protected $formKey;

    public function __construct(string $formKey, string $message)
    {
        $this->formKey = $formKey;

        parent::__construct($message);
    }

    public function getFormKey(): string
    {
        return $this->formKey;
    }
}
