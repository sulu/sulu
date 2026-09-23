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
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadata;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\ListBuilder\MediaLanguageFilterType;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Metadata\MediaLanguageListMetadataVisitor;
use Sulu\Bundle\MediaBundle\Media\MediaLanguage\MediaLanguageProvider;

class MediaLanguageListMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MediaLanguageProvider>
     */
    private ObjectProphecy $mediaLanguageProvider;

    private MediaLanguageListMetadataVisitor $visitor;

    protected function setUp(): void
    {
        $this->mediaLanguageProvider = $this->prophesize(MediaLanguageProvider::class);
        $this->visitor = new MediaLanguageListMetadataVisitor($this->mediaLanguageProvider->reveal());
    }

    public function testVisitListMetadataInjectsFilterOptions(): void
    {
        $this->mediaLanguageProvider->getLanguageNames('en')->willReturn(['de' => 'German', 'en' => 'English']);

        $listMetadata = new ListMetadata();
        $field = new FieldMetadata('mediaLanguage');
        $listMetadata->addField($field);

        $this->visitor->visitListMetadata($listMetadata, 'media', 'en');

        self::assertSame([
            'options' => [
                'de' => 'German',
                'en' => 'English',
                MediaLanguageFilterType::NONE_VALUE => 'sulu_media.media_language_none',
            ],
        ], $field->getFilterTypeParameters());
    }

    public function testVisitListMetadataIgnoresOtherLists(): void
    {
        $this->mediaLanguageProvider->getLanguageNames(\Prophecy\Argument::any())->shouldNotBeCalled();

        $listMetadata = new ListMetadata();
        $field = new FieldMetadata('mediaLanguage');
        $listMetadata->addField($field);

        $this->visitor->visitListMetadata($listMetadata, 'other', 'en');

        self::assertNull($field->getFilterTypeParameters());
    }
}
