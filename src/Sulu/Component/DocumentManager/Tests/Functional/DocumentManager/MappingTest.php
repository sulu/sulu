<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Functional\DocumentManager;

use Sulu\Component\DocumentManager\Tests\Functional\BaseTestCase;

class MappingTest extends BaseTestCase
{
    public function setUp()
    {
        $this->initPhpcr();
    }

    /**
     * It should map mapped fields.
     */
    public function testMapping()
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

    /**
     * It should map reference fields.
     */
    public function testMappingReference()
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
