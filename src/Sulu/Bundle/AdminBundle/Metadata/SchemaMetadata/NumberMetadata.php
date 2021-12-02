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

class NumberMetadata implements SchemaMetadataInterface
{
    /**
     * @var float|null
     */
    private $minimum;

    /**
     * @var float|null
     */
    private $maximum;

    /**
     * @var float|null
     */
    private $multipleOf;

    public function __construct(
        ?float $minimum = null,
        ?float $maximum = null,
        ?float $multipleOf = null
    ) {
        $this->minimum = $minimum;
        $this->maximum = $maximum;
        $this->multipleOf = $multipleOf;
    }

    public function toJsonSchema(): array
    {
        $jsonSchema = [
            'type' => $this->multipleOf && $this->isInteger($this->multipleOf)
                ? 'integer'
                : 'number',
        ];

        if (null !== $this->minimum) {
            $jsonSchema['minimum'] = $this->minimum;
        }

        if (null !== $this->maximum) {
            $jsonSchema['maximum'] = $this->maximum;
        }

        if (null !== $this->multipleOf) {
            $jsonSchema['multipleOf'] = $this->multipleOf;
        }

        return $jsonSchema;
    }

    private function isInteger(float $number): bool
    {
        return 0.0 === \fmod($number, 1);
    }
}
