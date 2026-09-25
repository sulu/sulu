<?php

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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;

class FormMetadataTest extends TestCase
{
    public function testFindTag(): void
    {
        $formMetadata = new FormMetadata();
        $tag1 = new TagMetadata();
        $tag1->setName('tag1');
        $formMetadata->addTag($tag1);
        $tag2 = new TagMetadata();
        $tag2->setName('tag2');
        $formMetadata->addTag($tag2);

        $this->assertSame(
            $tag1,
            $formMetadata->findTag('tag1'),
        );

        $this->assertSame(
            $tag2,
            $formMetadata->findTag('tag2'),
        );

        $this->assertNull($formMetadata->findTag('not-existing'));
    }

    public function testHasTag(): void
    {
        $formMetadata = new FormMetadata();
        $tag1 = new TagMetadata();
        $tag1->setName('tag1');
        $formMetadata->addTag($tag1);
        $tag2 = new TagMetadata();
        $tag2->setName('tag2');
        $formMetadata->addTag($tag2);

        $this->assertTrue($formMetadata->hasTag('tag1'));
        $this->assertTrue($formMetadata->hasTag('tag2'));
        $this->assertFalse($formMetadata->hasTag('not-existing'));
    }

    public function testGroup(): void
    {
        $formMetadata = new FormMetadata();
        $this->assertNull($formMetadata->getGroup());
        $formMetadata->setGroup('test-group');
        $this->assertSame('test-group', $formMetadata->getGroup());
    }

    public function testMergeCopiesTemplate(): void
    {
        $originalForm = new FormMetadata();
        $originalForm->setKey('test_key');

        $overrideForm = new FormMetadata();
        $overrideForm->setKey('test_key');

        $overrideTemplate = new TemplateMetadata('App\\Controller\\TestController', 'templates/default', null);
        $overrideForm->setTemplate($overrideTemplate);

        $mergedForm = $originalForm->merge($overrideForm);

        $this->assertSame('test_key', $mergedForm->getKey());
        $mergedTemplate = $mergedForm->getTemplate();
        $this->assertInstanceOf(TemplateMetadata::class, $mergedTemplate);
        $this->assertNotSame($overrideTemplate, $mergedForm->getTemplate());
        $this->assertSame('App\\Controller\\TestController', $mergedTemplate->getController());
        $this->assertSame('templates/default', $mergedTemplate->getView());
    }

    public function testMergeWithUninitializedTemplateMetadata(): void
    {
        $form1 = new FormMetadata();
        $form1->setKey('key1');

        $form2 = new FormMetadata();
        $form2->setKey('key1');

        $merged = $form1->merge($form2);

        $this->assertNull($merged->getTemplate());
        $this->assertSame('key1', $merged->getKey());
    }

    public function testMergePreservesGroup(): void
    {
        $originalForm = new FormMetadata();
        $originalForm->setKey('test_key');
        $originalForm->setGroup('original-group');

        $overrideForm = new FormMetadata();
        $overrideForm->setKey('test_key');

        // The group is kept when only the original form defines it.
        $this->assertSame('original-group', $originalForm->merge($overrideForm)->getGroup());

        // It is used when only the override form defines it.
        $originalForm->setGroup(null);
        $overrideForm->setGroup('override-group');
        $this->assertSame('override-group', $originalForm->merge($overrideForm)->getGroup());

        // The override form wins when both define a group (other wins over this, like the controller/view merge).
        $originalForm->setGroup('original-group');
        $this->assertSame('override-group', $originalForm->merge($overrideForm)->getGroup());

        // No group on either side stays null.
        $originalForm->setGroup(null);
        $overrideForm->setGroup(null);
        $this->assertNull($originalForm->merge($overrideForm)->getGroup());
    }

    public function testMergeWithGlobalBlocks(): void
    {
        $globalBlock1 = new FormMetadata();
        $globalBlock1->setKey('global_block1');

        $globalBlock2 = new FormMetadata();
        $globalBlock2->setKey('global_block2');

        $globalBlock3 = new FormMetadata();
        $globalBlock3->setKey('global_block3');

        $formMetaData1 = new FieldMetadata('field1');
        $formMetaData1->setType('type1');
        $formMetaData1->setMultilingual(false);
        $formMetaData1->setTypes([$globalBlock1, $globalBlock2]);

        $formMetaData2 = new FieldMetadata('field1');
        $formMetaData2->setType('type2');
        $formMetaData2->setMultilingual(true);
        $formMetaData2->setTypes([$globalBlock2, $globalBlock3]);

        $sectionMetaData1 = new SectionMetadata('section_field1');
        $sectionMetaData2 = new SectionMetadata('section_field2');

        $formMetaData3 = new FieldMetadata('field2');
        $formMetaData3->setType('type3');
        $formMetaData3->setMultilingual(true);
        $formMetaData3->setTypes([$globalBlock2, $globalBlock3]);

        $form1 = new FormMetadata();
        $form1->setKey('key1');
        $form1->setItems(['field1' => $formMetaData1, 'field2' => $formMetaData3, 'section_field1' => $sectionMetaData1]);

        $form2 = new FormMetadata();
        $form2->setKey('key1');
        $form2->setItems(['field1' => $formMetaData2, 'section_field1' => $sectionMetaData1, 'section_field2' => $sectionMetaData2]);

        $merged = $form1->merge($form2);

        $this->assertCount(4, $merged->getItems());
        $item = $merged->getItems()['field1'];
        $this->assertInstanceOf(FieldMetadata::class, $item);
        $this->assertEquals([
            'global_block1' => $globalBlock1,
            'global_block2' => $globalBlock2,
            'global_block3' => $globalBlock3,
        ], $item->getTypes());

        $this->assertSame($formMetaData3, $merged->getItems()['field2']);
        $this->assertSame($sectionMetaData1, $merged->getItems()['section_field1']);
        $this->assertSame($sectionMetaData2, $merged->getItems()['section_field2']);
    }

    public function testMergeDoesNotMergeSectionsWithSameName(): void
    {
        $sectionMetaData1 = new SectionMetadata('section_field');
        $sectionMetaData2 = new SectionMetadata('section_field');

        $form1 = new FormMetadata();
        $form1->setKey('key1');
        $form1->setItems([
            'section_field' => $sectionMetaData1,
        ]);

        $form2 = new FormMetadata();
        $form2->setKey('key1');
        $form2->setItems([
            'section_field' => $sectionMetaData2,
        ]);

        $merged = $form1->merge($form2);

        $this->assertCount(1, $merged->getItems());
        $this->assertSame(
            $sectionMetaData1,
            $merged->getItems()['section_field']
        );
    }

    public function testMergeAddsSectionsFromOtherForm(): void
    {
        $sectionMetaData1 = new SectionMetadata('section_field1');
        $sectionMetaData2 = new SectionMetadata('section_field2');

        $form1 = new FormMetadata();
        $form1->setKey('key1');
        $form1->setItems([
            'section_field1' => $sectionMetaData1,
        ]);

        $form2 = new FormMetadata();
        $form2->setKey('key1');
        $form2->setItems([
            'section_field2' => $sectionMetaData2,
        ]);

        $merged = $form1->merge($form2);

        $this->assertCount(2, $merged->getItems());
        $this->assertSame($sectionMetaData1, $merged->getItems()['section_field1']);
        $this->assertSame($sectionMetaData2, $merged->getItems()['section_field2']);
    }
}
