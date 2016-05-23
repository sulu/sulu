<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Repository;

use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\ContentBundle\Repository\ResourceLocatorRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * @group functional
 * @group repository
 */
class ResourceLocatorRepositoryTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var ResourceLocatorRepositoryInterface
     */
    private $repository;

    protected function setUp()
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->session = $this->getContainer()->get('doctrine_phpcr.default_session');
        $this->repository = $this->getContainer()->get('sulu_content.rl_repository');
    }

    public function testGenerate()
    {
        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setTitle('test');
        $document->setResourceSegment('/test');
        $document->setStructureType('overview');
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $result = $this->repository->generate(
            [
                'title' => 'test',
            ],
            $document->getUuid(),
            null,
            'sulu_io',
            'en',
            'overview'
        );

        $this->assertEquals('/test/test', $result['resourceLocator']);
    }

    public function testGenerateSlash()
    {
        $result = $this->repository->generate(['title' => 'Title / Header'], null, null, 'sulu_io', 'en', 'overview');

        $this->assertEquals('/title-header', $result['resourceLocator']);
    }

    /**
     * @return \Sulu\Component\Content\Compat\StructureInterface
     */
    private function prepareHistoryTestData()
    {
        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setTitle('test-1');
        $document->setResourceSegment('/test');
        $document->setStructureType('overview');
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        sleep(1); // required because of jackrabbit

        $document->setResourceSegment('/test-1');
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        sleep(1); // required because of jackrabbit

        $document->setResourceSegment('/test-2');
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        return $document;
    }

    public function testGetHistory()
    {
        $structure = $this->prepareHistoryTestData();

        $result = $this->repository->getHistory($structure->getUuid(), 'sulu_io', 'en');

        $this->assertEquals(2, count($result['_embedded']['resourcelocators']));
        $this->assertEquals('/test-1', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
        $this->assertEquals('/test', $result['_embedded']['resourcelocators'][1]['resourceLocator']);
    }

    public function testRestore()
    {
        $structure = $this->prepareHistoryTestData();

        $result = $this->repository->restore('/test', 1, 'sulu_io', 'en');

        $this->assertEquals('/test', $result['resourceLocator']);

        $result = $this->repository->getHistory($structure->getUuid(), 'sulu_io', 'en');

        $this->assertEquals(2, count($result['_embedded']['resourcelocators']));
        $this->assertEquals('/test-2', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
        $this->assertEquals('/test-1', $result['_embedded']['resourcelocators'][1]['resourceLocator']);
    }

    public function testDelete()
    {
        $structure = $this->prepareHistoryTestData();

        $this->repository->delete('/test', 'sulu_io', 'en');
        $this->session->save();
        $result = $this->repository->getHistory($structure->getUuid(), 'sulu_io', 'en');

        $this->assertEquals(1, count($result['_embedded']['resourcelocators']));
        $this->assertEquals('/test-1', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
    }
}
