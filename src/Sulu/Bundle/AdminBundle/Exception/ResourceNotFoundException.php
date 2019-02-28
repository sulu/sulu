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

/**
 * An instance of this exception signals that no resource with given key was found.
 */
class ResourceNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $resourceKey;

    public function __construct(string $resourceKey)
    {
        parent::__construct(sprintf('The resource with the key "%s" does not exist.', $resourceKey));

        $this->resourceKey = $resourceKey;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }
}
