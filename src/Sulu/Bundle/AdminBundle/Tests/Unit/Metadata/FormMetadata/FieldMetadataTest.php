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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;

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

    public function testMerge(): void
    {
        $metadata = new FieldMetadata('field');
        $metadata->setType('text');
        $metadata->setColSpan(6);
        $metadata->setDefaultType('default');
        $metadata->setMaxOccurs(10);
        $metadata->setMinOccurs(2);
        $metadata->setOnInvalid('show-error');
        $metadata->setRequired(true);
        $metadata->setDescriptions(['de' => 'Beschreibung basis']);
        $metadata->setDisabledCondition('disabled');
        $metadata->setVisibleCondition('visible');
        $metadata->setSpaceAfter(20);
        $metadata->setMultilingual(true);
        $metadata->setLabels(['de' => 'Label']);

        $tag = new TagMetadata();
        $tag->setName('tag');

        $type = $this->setupFormMetadata('type');

        $metadata->setTags([$tag]);
        $metadata->setTypes([$type]);

        $otherMetadata = new FieldMetadata('other-field');
        $otherMetadata->setMultilingual(true);
        $otherMetadata->setLabels(['de' => 'Label override']);
        $otherMetadata->setDescriptions(['de' => 'Beschreibung override']);

        $otherTag = new TagMetadata();
        $otherTag->setName('other-tag');

        $otherType = $this->setupFormMetadata('other-type');

        $otherMetadata->setTags([$otherTag]);
        $otherMetadata->setTypes([$otherType]);

        $mergedMetadata = $metadata->merge($otherMetadata);

        self::assertSame('field', $mergedMetadata->getName());
        self::assertSame('text', $mergedMetadata->getType());
        self::assertSame(6, $mergedMetadata->getColSpan());
        self::assertSame('default', $mergedMetadata->getDefaultType());
        self::assertSame(10, $mergedMetadata->getMaxOccurs());
        self::assertSame(2, $mergedMetadata->getMinOccurs());
        self::assertSame('show-error', $mergedMetadata->getOnInvalid());
        self::assertTrue($mergedMetadata->isRequired());
        self::assertSame(['de' => 'Beschreibung override'], $mergedMetadata->getDescriptions());
        self::assertSame('disabled', $mergedMetadata->getDisabledCondition());
        self::assertSame('visible', $mergedMetadata->getVisibleCondition());
        self::assertSame(20, $mergedMetadata->getSpaceAfter());
        self::assertTrue($mergedMetadata->isMultilingual());

        self::assertSame(['de' => 'Label override'], $mergedMetadata->getLabels());
        self::assertSame([$tag, $otherTag], $mergedMetadata->getTags());
        self::assertSame(
            [
                'type' => $type,
                'other-type' => $otherType,
            ],
            $mergedMetadata->getTypes(),
        );
    }

    private function setupFormMetadata(string $name): FormMetadata
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey($name);

        return $formMetadata;
    }
}
