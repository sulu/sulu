<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata\Schema;

class Schema
{
    /**
     * @var string[]
     */
    protected $required;

    /**
     * @return string[]
     */
    public function getRequired(): array
    {
        return $this->required;
    }

    /**
     * @param string[] $required
     */
    public function setRequired(array $required): void
    {
        $this->required = $required;
    }

    public function addRequired(string $required): void
    {
        $this->required[] = $required;
    }
}
