<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Exception;

class MetadataProviderNotFoundException extends \Exception
{
    private string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
        parent::__construct(\sprintf('There is no MetadataProvider registered for the type "%s".', $this->type));
    }

    public function getType(): string
    {
        return $this->type;
    }
}
