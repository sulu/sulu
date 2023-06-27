<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Template\Exception;

class ReservedPropertyNameException extends InvalidXmlException
{
    /**
     * @param string $template The template causing the problem
     * @param string $propertyName The name of the property, which has been used
     */
    public function __construct($template, protected $propertyName)
    {
        parent::__construct(
            $template,
            \sprintf(
                'The property with the name "%s" was used by the template "%s", although it is a reserved property name',
                $this->propertyName,
                $template
            )
        );
    }

    /**
     * Returns the name of the property, which was not allowed to be used.
     *
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }
}
