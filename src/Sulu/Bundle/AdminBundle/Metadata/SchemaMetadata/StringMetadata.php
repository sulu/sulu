<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

class StringMetadata implements SchemaMetadataInterface
{
    /**
     * @var int|null
     */
    private $minLength;

    /**
     * @var int|null
     */
    private $maxLength;

    /**
     * @var string|null
     */
    private $pattern;

    public function __construct(?int $minLength = null, ?int $maxLength = null, ?string $pattern = null)
    {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->pattern = $pattern;
    }

    public function toJsonSchema(): array
    {
        $jsonSchema = [
            'type' => 'string',
        ];

        if (null !== $this->minLength) {
            $jsonSchema['minLength'] = $this->minLength;
        }

        if (null !== $this->maxLength) {
            $jsonSchema['maxLength'] = $this->maxLength;
        }

        if (null !== $this->pattern) {
            $jsonSchema['pattern'] = $this->pattern;
        }

        return $jsonSchema;
    }
}
