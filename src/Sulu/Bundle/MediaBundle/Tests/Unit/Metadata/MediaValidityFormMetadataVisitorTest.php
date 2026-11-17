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
    public function testVisitHidesValidityFieldsWhenDisabled(): void
    {
        [$formMetadata, $validFrom, $validTo, $title] = $this->createFormMetadata('media_details');

        (new MediaValidityFormMetadataVisitor(false))->visitFormMetadata($formMetadata, 'en');

        $this->assertSame('false', $validFrom->getVisibleCondition());
        $this->assertSame('false', $validTo->getVisibleCondition());
        $this->assertNull($title->getVisibleCondition());
    }

    public function testVisitKeepsValidityFieldsWhenEnabled(): void
    {
        [$formMetadata, $validFrom, $validTo] = $this->createFormMetadata('media_details');

        (new MediaValidityFormMetadataVisitor(true))->visitFormMetadata($formMetadata, 'en');

        $this->assertNull($validFrom->getVisibleCondition());
        $this->assertNull($validTo->getVisibleCondition());
    }

    public function testVisitIgnoresOtherForms(): void
    {
        [$formMetadata, $validFrom, $validTo] = $this->createFormMetadata('collection_details');

        (new MediaValidityFormMetadataVisitor(false))->visitFormMetadata($formMetadata, 'en');

        $this->assertNull($validFrom->getVisibleCondition());
        $this->assertNull($validTo->getVisibleCondition());
    }

    public function testVisitWithoutValidityFields(): void
    {
        $detailsSection = new SectionMetadata('media_details');
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('media_details');
        $formMetadata->addItem($detailsSection);

        (new MediaValidityFormMetadataVisitor(false))->visitFormMetadata($formMetadata, 'en');

        $this->assertSame([], $detailsSection->getItems());
    }

    /**
     * @return array{FormMetadata, FieldMetadata, FieldMetadata, FieldMetadata}
     */
    private function createFormMetadata(string $key): array
    {
        $title = new FieldMetadata('title');
        $validFrom = new FieldMetadata('validFrom');
        $validTo = new FieldMetadata('validTo');

        $detailsSection = new SectionMetadata('media_details');
        $detailsSection->addItem($title);
        $detailsSection->addItem($validFrom);
        $detailsSection->addItem($validTo);

        $formMetadata = new FormMetadata();
        $formMetadata->setKey($key);
        $formMetadata->addItem($detailsSection);

        return [$formMetadata, $validFrom, $validTo, $title];
    }
}
