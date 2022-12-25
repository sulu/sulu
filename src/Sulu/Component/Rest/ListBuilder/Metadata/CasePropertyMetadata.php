<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

/**
 * Describes a field which decide between two descriptors.
 */
class CasePropertyMetadata extends AbstractPropertyMetadata
{
    /**
     * @var FieldMetadata[]
     */
    private array $cases = [];

    /**
     * Returns all cases.
     *
     * @return FieldMetadata[]
     */
    public function getCases(): array
    {
        return $this->cases;
    }

    /**
     * Returns a single case.
     */
    public function getCase(int $index): FieldMetadata
    {
        return $this->cases[$index];
    }

    /**
     * Add a case.
     */
    public function addCase(FieldMetadata $case): void
    {
        $this->cases[] = $case;
    }
}
