<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Exception;

/**
 * Indicates a missing provider.
 */
class ProviderNotFoundException extends PreviewException
{
    /**
     * @var string
     */
    private $objectClass;

    /**
     * @param string $objectClass
     */
    public function __construct($objectClass)
    {
        parent::__construct(sprintf('No provider found for object class "%s"', $objectClass), 9900);

        $this->objectClass = $objectClass;
    }

    /**
     * Returns objectClass.
     *
     * @return string
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }
}
