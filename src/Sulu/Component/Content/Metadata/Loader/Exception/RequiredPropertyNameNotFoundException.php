<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param string $propertyName
     */
    public function __construct($template, protected $propertyName)
    {
        parent::__construct(
            $template,
            \sprintf(
                'The property with the name "%s" is required, but was not found in the template "%s"',
                $this->propertyName,
                $template
            )
        );
    }
}
