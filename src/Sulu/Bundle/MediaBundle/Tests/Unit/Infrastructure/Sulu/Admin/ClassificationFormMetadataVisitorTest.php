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

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Infrastructure\Sulu\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Admin\ClassificationFormMetadataVisitor;
use Sulu\Bundle\MediaBundle\Media\Classification\ClassificationProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ClassificationFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    public function testHidesTheFieldAndWidensContentLocalesWithoutClassifications(): void
    {
        $formMetadata = $this->createFormMetadata('media_details');

        $this->createVisitor([])->visitFormMetadata($formMetadata, 'en');

        $this->assertSame('false', $this->getField($formMetadata, 'classification')->getVisibleCondition());
        $this->assertSame(12, $this->getField($formMetadata, 'contentLocales')->getColSpan());
    }

    public function testKeepsTheFieldWithClassifications(): void
    {
        $formMetadata = $this->createFormMetadata('media_details');

        $this->createVisitor(['data_sheet' => 'app.data_sheet'])->visitFormMetadata($formMetadata, 'en');

        $this->assertNull($this->getField($formMetadata, 'classification')->getVisibleCondition());
        $this->assertSame(6, $this->getField($formMetadata, 'contentLocales')->getColSpan());
    }

    public function testIgnoresOtherForms(): void
    {
        $formMetadata = $this->createFormMetadata('other_form');

        $this->createVisitor([])->visitFormMetadata($formMetadata, 'en');

        $this->assertNull($this->getField($formMetadata, 'classification')->getVisibleCondition());
        $this->assertSame(6, $this->getField($formMetadata, 'contentLocales')->getColSpan());
    }

    /**
     * @param array<string, string> $classifications
     */
    private function createVisitor(array $classifications): ClassificationFormMetadataVisitor
    {
        return new ClassificationFormMetadataVisitor(
            new ClassificationProvider($classifications, $this->prophesize(TranslatorInterface::class)->reveal())
        );
    }

    private function createFormMetadata(string $key): FormMetadata
    {
        $contentLocales = new FieldMetadata('contentLocales');
        $contentLocales->setColSpan(6);
        $classification = new FieldMetadata('classification');
        $classification->setColSpan(6);

        $section = new SectionMetadata('details');
        $section->addItem($contentLocales);
        $section->addItem($classification);

        $formMetadata = new FormMetadata();
        $formMetadata->setKey($key);
        $formMetadata->addItem($section);

        return $formMetadata;
    }

    private function getField(FormMetadata $formMetadata, string $name): FieldMetadata
    {
        $section = $formMetadata->getItems()['details'];
        \assert($section instanceof SectionMetadata);
        $field = $section->getItems()[$name];
        \assert($field instanceof FieldMetadata);

        return $field;
    }
}
