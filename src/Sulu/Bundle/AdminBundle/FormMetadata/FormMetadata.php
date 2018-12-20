<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\FormMetadata;

use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Schema;
use Sulu\Component\Content\Metadata\PropertiesMetadata;

/**
 * Represents metadata for a form structure.
 */
class FormMetadata extends PropertiesMetadata
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var Schema
     */
    private $schema;

    public function setKey(string $key)
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function getSchema(): ?Schema
    {
        return $this->schema;
    }
}
