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

    public function __construct($blockPropertyName, $propertyName)
    {
        $this->blockPropertyName = $blockPropertyName;
        $this->propertyName = $propertyName;

        parent::__construct(
            \sprintf(
                'Block "%s" defines has a child property named "%s", although it is a reserved property name',
                $this->blockPropertyName,
                $this->propertyName
            )
        );
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
