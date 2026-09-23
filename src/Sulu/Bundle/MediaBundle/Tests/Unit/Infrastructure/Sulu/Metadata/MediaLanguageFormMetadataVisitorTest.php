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

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Infrastructure\Sulu\Metadata;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Metadata\MediaLanguageFormMetadataVisitor;
use Sulu\Bundle\MediaBundle\Media\MediaLanguage\MediaLanguageProvider;

class MediaLanguageFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MediaLanguageProvider>
     */
    private ObjectProphecy $mediaLanguageProvider;

    private MediaLanguageFormMetadataVisitor $visitor;

    protected function setUp(): void
    {
        $this->mediaLanguageProvider = $this->prophesize(MediaLanguageProvider::class);
        $this->visitor = new MediaLanguageFormMetadataVisitor($this->mediaLanguageProvider->reveal());
    }

    public function testVisitFormMetadataInjectsLanguageOptions(): void
    {
        $this->mediaLanguageProvider->getLanguageNames('en')->willReturn(['de' => 'German', 'en' => 'English']);

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('media_details');
        $mediaLanguagesField = new FieldMetadata('mediaLanguages');
        // the real form nests the field inside a section
        $section = new SectionMetadata('media_details');
        $section->addItem($mediaLanguagesField);
        $formMetadata->addItem($section);

        $this->visitor->visitFormMetadata($formMetadata, 'en');

        $values = $mediaLanguagesField->getOptions()['values'];
        self::assertSame('collection', $values->getType());

        $optionValues = $values->getValue();
        self::assertIsArray($optionValues);
        self::assertCount(2, $optionValues);
        self::assertSame('de', $optionValues[0]->getValue());
        self::assertSame('German', $optionValues[0]->getTitle('en'));
        self::assertSame('en', $optionValues[1]->getValue());
    }

    public function testVisitFormMetadataIgnoresOtherForms(): void
    {
        $this->mediaLanguageProvider->getLanguageNames(\Prophecy\Argument::any())->shouldNotBeCalled();

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('other');
        $mediaLanguagesField = new FieldMetadata('mediaLanguages');
        $formMetadata->addItem($mediaLanguagesField);

        $this->visitor->visitFormMetadata($formMetadata, 'en');

        self::assertArrayNotHasKey('values', $mediaLanguagesField->getOptions());
    }
}
