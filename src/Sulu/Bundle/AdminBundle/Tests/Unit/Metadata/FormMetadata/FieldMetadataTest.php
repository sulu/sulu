<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;

class FieldMetadataTest extends TestCase
{
    public function testFindOption(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');
        $minOption = new OptionMetadata();
        $minOption->setName('min');
        $minOption->setValue(2);
        $fieldMetadata->addOption($minOption);
        $maxOption = new OptionMetadata();
        $maxOption->setName('max');
        $maxOption->setValue(3);
        $fieldMetadata->addOption($maxOption);

        $this->assertSame(
            $minOption,
            $fieldMetadata->findOption('min')
        );

        $this->assertSame(
            $maxOption,
            $fieldMetadata->findOption('max')
        );

        $this->assertNull($fieldMetadata->findOption('not-existing'));
    }

    public function testAddTypeAddsToTypes(): void
    {
        $formMetadata1 = $this->setupFormMetadata('dummy1');
        $formMetadata2 = $this->setupFormMetadata('dummy2');

        $metaData = new FieldMetadata('dummy');
        $metaData->addType($formMetadata1);
        $metaData->addType($formMetadata2);

        $this->assertSame([
            'dummy1' => $formMetadata1,
            'dummy2' => $formMetadata2,
        ], $metaData->getTypes());
    }

    public function testRemoveTypeRemovesFromTypes(): void
    {
        $formMetadata1 = $this->setupFormMetadata('dummy1');
        $formMetadata2 = $this->setupFormMetadata('dummy2');

        $metaData = new FieldMetadata('dummy');
        $metaData->addType($formMetadata1);
        $metaData->addType($formMetadata2);

        $metaData->removeType('dummy1');

        $this->assertSame([
            'dummy2' => $formMetadata2,
        ], $metaData->getTypes());
    }

    public function testRemoveTypeIgnoresUnknownType(): void
    {
        $formMetadata1 = $this->setupFormMetadata('dummy1');
        $formMetadata2 = $this->setupFormMetadata('dummy2');

        $metaData = new FieldMetadata('dummy');
        $metaData->addType($formMetadata1);
        $metaData->addType($formMetadata2);

        $metaData->removeType('unknown');

        $this->assertSame([
            'dummy1' => $formMetadata1,
            'dummy2' => $formMetadata2,
        ], $metaData->getTypes());
    }

    private function setupFormMetadata(string $name): FormMetadata
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey($name);

        return $formMetadata;
    }
}
