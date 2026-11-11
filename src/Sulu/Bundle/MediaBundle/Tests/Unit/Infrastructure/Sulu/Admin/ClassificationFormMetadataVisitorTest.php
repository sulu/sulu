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

    public function testHidesTheFieldWithoutClassifications(): void
    {
        $formMetadata = $this->createFormMetadata('media_details');

        $this->createVisitor([])->visitFormMetadata($formMetadata, 'en');

        $this->assertSame('false', $this->getClassificationField($formMetadata)->getVisibleCondition());
    }

    public function testKeepsTheFieldWithClassifications(): void
    {
        $formMetadata = $this->createFormMetadata('media_details');

        $this->createVisitor(['data_sheet' => 'app.data_sheet'])->visitFormMetadata($formMetadata, 'en');

        $this->assertNull($this->getClassificationField($formMetadata)->getVisibleCondition());
    }

    public function testIgnoresOtherForms(): void
    {
        $formMetadata = $this->createFormMetadata('other_form');

        $this->createVisitor([])->visitFormMetadata($formMetadata, 'en');

        $this->assertNull($this->getClassificationField($formMetadata)->getVisibleCondition());
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
        $section = new SectionMetadata('details');
        $section->addItem(new FieldMetadata('classification'));

        $formMetadata = new FormMetadata();
        $formMetadata->setKey($key);
        $formMetadata->addItem($section);

        return $formMetadata;
    }

    private function getClassificationField(FormMetadata $formMetadata): FieldMetadata
    {
        $section = $formMetadata->getItems()['details'];
        \assert($section instanceof SectionMetadata);
        $field = $section->getItems()['classification'];
        \assert($field instanceof FieldMetadata);

        return $field;
    }
}
