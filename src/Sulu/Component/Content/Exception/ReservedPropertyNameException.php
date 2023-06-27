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

class ReservedPropertyNameException extends InvalidFieldMetadataException
{
    /**
     * @param string $blockPropertyName
     * @param string $propertyName
     * @param string $formKey
     */
    public function __construct(
        private $blockPropertyName,
        private $propertyName,
        $formKey
    ) {
        $this->blockPropertyName = $blockPropertyName;
        $this->propertyName = $propertyName;

        parent::__construct(
            $formKey,
            \sprintf(
                'Block "%s" in form "%s" has a child property named "%s", although it is a reserved property name',
                $this->blockPropertyName,
                $formKey,
                $this->propertyName
            )
        );
    }

    /**
     * @return string
     */
    public function getBlockPropertyName()
    {
        return $this->blockPropertyName;
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }
}
