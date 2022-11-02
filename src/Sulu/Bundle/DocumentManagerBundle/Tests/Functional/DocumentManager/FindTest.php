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

class FindTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->initPhpcr();
    }

    /**
     * Persist a document in a single locale.
     */
    public function testPersist(): void
    {
        $this->generateDataSet([
            'locales' => ['en'],
        ]);

        $manager = $this->getDocumentManager();
        $manager->flush();

        $document = $manager->find(self::BASE_PATH);
        $this->assertNotNull($document);
    }

    /**
     * Persist a document in a many locales.
     */
    public function testPersistManyLocales(): void
    {
        $this->generateDataSet([
            'locales' => ['en', 'de'],
        ]);

        $manager = $this->getDocumentManager();
        $manager->flush();

        $document = $manager->find(self::BASE_PATH);
        $this->assertNotNull($document);
    }

    /**
     * It can persist and find without any locales.
     */
    public function testPersistFindNoLocales(): void
    {
        $manager = $this->getDocumentManager();
        $document = $manager->create('full');
        $document->setTitle('Hello');
        $document->setBody('This is Hello');
        $document->setStatus('open');
        $manager->persist($document, null, [
            'path' => '/test/foo',
            'auto_create' => true,
        ]);
        $manager->flush();

        $manager->clear();
        $persistedDocument = $manager->find($document->getUuid());
        $this->assertNotSame($document, $persistedDocument);
        $document = $persistedDocument;
        $this->assertEquals('en', $document->getLocale());
    }
}
