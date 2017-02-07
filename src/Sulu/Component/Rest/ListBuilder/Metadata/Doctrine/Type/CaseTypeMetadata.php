<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type;

use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;

/**
 * Describes a field which decide between two descriptors.
 */
class CaseTypeMetadata
{
    /**
     * @var FieldMetadata[]
     */
    private $cases = [];

    /**
     * Returns all cases.
     *
     * @return FieldMetadata[]
     */
    public function getCases()
    {
        return $this->cases;
    }

    /**
     * Returns a single case.
     *
     * @param int $index
     *
     * @return FieldMetadata
     */
    public function getCase($index)
    {
        return $this->cases[$index];
    }

    /**
     * Add a case.
     *
     * @param FieldMetadata $case
     */
    public function addCase(FieldMetadata $case)
    {
        $this->cases[] = $case;
    }
}
