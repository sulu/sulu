<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Exception;

class MetadataProviderNotFoundException extends \Exception
{
    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;
        parent::__construct(sprintf('There is no MetadataProvider registered for the type "%s".', $this->type));
    }

    public function getType()
    {
        return $this->type;
    }
}
