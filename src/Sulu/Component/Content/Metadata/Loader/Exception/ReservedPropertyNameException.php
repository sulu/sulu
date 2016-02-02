<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Loader\Exception;

/**
 * Thrown when a template does not contain a reserved property name.
 */
class ReservedPropertyNameException extends InvalidXmlException
{
    /**
     * The reserved property name, which has been used.
     *
     * @var string
     */
    protected $propertyName;

    /**
     * @param string $template     The template causing the problem
     * @param string $propertyName The name of the property, which has been used
     */
    public function __construct($template, $propertyName)
    {
        $this->propertyName = $propertyName;

        parent::__construct(
            $template,
            sprintf(
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
