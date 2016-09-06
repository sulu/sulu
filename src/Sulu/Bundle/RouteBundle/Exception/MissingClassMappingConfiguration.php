<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Exception;

/**
 * Missing class mapping configuration exception.
 */
class MissingClassMappingConfiguration extends \Exception
{
    public function __construct($className) {
        parent::__construct(sprintf('Missing class mapping configuration for "%s"', $className));
    }
}
