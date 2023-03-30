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
    private ?int $minLength = null;

    private ?int $maxLength = null;

    private ?string $pattern = null;

    private ?string $format = null;

    public function __construct(
        ?int $minLength = null,
        ?int $maxLength = null,
        ?string $pattern = null,
        ?string $format = null
    ) {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->pattern = $pattern;
        $this->format = $format;
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

        if (null !== $this->format) {
            $jsonSchema['format'] = $this->format;
        }

        return $jsonSchema;
    }
}
