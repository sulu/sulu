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
            $formMetadata->findTag('tag1')
        );

        $this->assertSame(
            $tag2,
            $formMetadata->findTag('tag2')
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
}
