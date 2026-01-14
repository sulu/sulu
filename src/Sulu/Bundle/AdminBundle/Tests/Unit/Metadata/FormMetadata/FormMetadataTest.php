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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
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
}
