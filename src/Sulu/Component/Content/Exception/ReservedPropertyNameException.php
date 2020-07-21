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

class ReservedPropertyNameException extends \Exception
{
    /**
     * @var string
     */
    private $blockPropertyName;

    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var string
     */
    private $formKey;

    public function __construct($blockPropertyName, $propertyName, $formKey)
    {
        $this->blockPropertyName = $blockPropertyName;
        $this->propertyName = $propertyName;
        $this->formKey = $formKey;

        parent::__construct(
            \sprintf(
                'Block "%s" in form "%s" has a child property named "%s", although it is a reserved property name',
                $this->blockPropertyName,
                $this->formKey,
                $this->propertyName
            )
        );
    }

    public function getFormKey()
    {
        return $this->formKey;
    }

    public function getBlockPropertyName()
    {
        return $this->blockPropertyName;
    }

    public function getPropertyName()
    {
        return $this->propertyName;
    }
}
