<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Functional\DocumentManager;

use Sulu\Bundle\DocumentManagerBundle\Tests\Functional\BaseTestCase;

class MappingTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->initPhpcr();
    }

    public function testMapping(): void
    {
        $document = $this->getDocumentManager()->create('full');
        $document->setTitle('Hallo');
        $document->setBody('body');
        $document->setStatus('published');

        $this->getDocumentManager()->persist($document, 'de', [
            'path' => '/test/foo',
            'auto_create' => true,
        ]);
        $this->getDocumentManager()->flush();

        $node = $this->getSession()->getNode('/test/foo');

        foreach ([
            'lcon:de-title' => 'Hallo',
            'lcon:de-body' => 'body',
            'nsys:my_status' => 'published',
        ] as $phpcrName => $expectedValue) {
            $this->assertEquals($expectedValue, $node->getPropertyValue($phpcrName));
        }
    }

    public function testMappingReference(): void
    {
        $manager = $this->getDocumentManager();

        $reference = $manager->create('full');
        $manager->persist($reference, 'de', [
            'path' => '/test/foo',
            'auto_create' => true,
        ]);
        $manager->flush();

        $document = $manager->create('full');
        $document->setReference($reference);
        $manager->persist($document, 'de', [
            'path' => '/test/boo',
            'auto_create' => true,
        ]);
        $manager->flush();
        $manager->clear();

        $document = $manager->find('/test/boo');
        $this->assertNotNull($document->getReference());
        $this->assertEquals(
            $reference->getUuid(),
            $document->getReference()->getUuid()
        );
    }
}
