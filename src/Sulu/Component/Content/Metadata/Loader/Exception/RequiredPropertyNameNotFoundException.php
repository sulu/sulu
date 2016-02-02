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
 * Thrown when a template does not contain a required property name.
 */
class RequiredPropertyNameNotFoundException extends InvalidXmlException
{
    /**
     * The name of the property, which is required, but not found.
     *
     * @var string
     */
    protected $propertyName;

    public function __construct($template, $propertyName)
    {
        $this->propertyName = $propertyName;
        parent::__construct(
            $template,
            sprintf(
                'The property with the name "%s" is required, but was not found in the template "%s"',
                $this->propertyName,
                $template
            )
        );
    }
}
