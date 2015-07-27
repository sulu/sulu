<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\SmartContent;

use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * Indicates missing mandatory property parameter
 */
class MissingMandatoryParameterException extends \Exception
{
    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var string
     */
    private $parameterName;

    public function __construct(PropertyInterface $property, $parameterName)
    {
        $this->property = $property;
        $this->parameterName = $parameterName;
    }

    /**
     * Returns property with missing parameter
     *
     * @return PropertyInterface
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Returns name of missing parameter
     *
     * @return string
     */
    public function getParameterName()
    {
        return $this->parameterName;
    }
}
