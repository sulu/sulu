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
class RequiredTagNotFoundException extends InvalidXmlException
{
    /**
     * The name of the property, which is required, but not found.
     *
     * @var string
     */
    protected $tagName;

    public function __construct($template, $tagName)
    {
        $this->tagName = $tagName;
        parent::__construct(
            $template,
            sprintf(
                'The tag with the name "%s" is required, but was not found in the template "%s"',
                $this->tagName,
                $template
            )
        );
    }
}
