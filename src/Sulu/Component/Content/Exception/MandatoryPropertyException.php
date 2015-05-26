<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

use Sulu\Component\Content\PropertyInterface;

/**
 * data for mandatory exception not found.
 */
class MandatoryPropertyException extends \Exception
{
    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var string
     */
    private $templateKey;

    public function __construct($templateKey, PropertyInterface $property)
    {
        parent::__construct(
            sprintf('Data for mandatory property %s in template %s not found', $property->getName(), $templateKey)
        );
        $this->property = $property;
        $this->templateKey = $templateKey;
    }

    /**
     * @return \Sulu\Component\Content\PropertyInterface
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @return string
     */
    public function getTemplateKey()
    {
        return $this->templateKey;
    }
}
