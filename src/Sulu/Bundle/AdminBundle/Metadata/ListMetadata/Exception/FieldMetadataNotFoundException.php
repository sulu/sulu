<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\ListMetadata\Exception;

use Exception;

class FieldMetadataNotFoundException extends Exception
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;

        parent::__construct(
            \sprintf('There is no FieldMetadata available with the name "%s".', $this->name)
        );
    }

    public function getName(): string
    {
        return $this->name;
    }
}
