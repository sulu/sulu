<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Metadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\MediaBundle\Metadata\MediaValidityFormMetadataVisitor;

class MediaValidityFormMetadataVisitorTest extends TestCase
{
    public function testVisitHidesValiditySectionWhenDisabled(): void
    {
        [$formMetadata, $validitySection] = $this->createFormMetadata('media_details');

        (new MediaValidityFormMetadataVisitor(false))->visitFormMetadata($formMetadata, 'en');

        $this->assertSame('false', $validitySection->getVisibleCondition());
    }

    public function testVisitKeepsValiditySectionWhenEnabled(): void
    {
        [$formMetadata, $validitySection] = $this->createFormMetadata('media_details');

        (new MediaValidityFormMetadataVisitor(true))->visitFormMetadata($formMetadata, 'en');

        $this->assertNull($validitySection->getVisibleCondition());
    }

    public function testVisitIgnoresOtherForms(): void
    {
        [$formMetadata, $validitySection] = $this->createFormMetadata('collection_details');

        (new MediaValidityFormMetadataVisitor(false))->visitFormMetadata($formMetadata, 'en');

        $this->assertNull($validitySection->getVisibleCondition());
    }

    public function testVisitWithoutValiditySection(): void
    {
        $detailsSection = new SectionMetadata('media_details');
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('media_details');
        $formMetadata->addItem($detailsSection);

        (new MediaValidityFormMetadataVisitor(false))->visitFormMetadata($formMetadata, 'en');

        $this->assertSame([], $detailsSection->getItems());
    }

    /**
     * @return array{FormMetadata, SectionMetadata}
     */
    private function createFormMetadata(string $key): array
    {
        $validitySection = new SectionMetadata('validity');
        $validitySection->addItem(new FieldMetadata('validFrom'));
        $validitySection->addItem(new FieldMetadata('validTo'));

        $detailsSection = new SectionMetadata('media_details');
        $detailsSection->addItem($validitySection);

        $formMetadata = new FormMetadata();
        $formMetadata->setKey($key);
        $formMetadata->addItem($detailsSection);

        return [$formMetadata, $validitySection];
    }
}
