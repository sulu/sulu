<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Import;

use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Import\WebspaceImport;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests for the Webspace Export class.
 */
class WebspaceImportTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var object
     */
    private $homeDocument;

    private $pages = [];

    /**
     * @var WebspaceImport
     */
    private $webspaceImporter;

    protected $distPath = __DIR__ . '/../../fixtures/import/export.xliff.dist';

    protected $distPathRU = __DIR__ . '/../../fixtures/import/export_ru.xliff.dist';

    protected $distPathBlockInBlock = __DIR__ . '/../../fixtures/import/export_block_in_block.xliff.dist';

    protected $path = __DIR__ . '/../../fixtures/import/export.xliff';

    protected $pathRU = __DIR__ . '/../../fixtures/import/export_ru.xliff';

    protected $pathBlockInBlock = __DIR__ . '/../../fixtures/import/export_block_in_block.xliff';

    /**
     * Setup data for import.
     */
    protected function setUp(): void
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->homeDocument = $this->documentManager->find('/cmf/sulu_io/contents', 'en');
        $this->webspaceImporter = $this->getContainer()->get('sulu_page.import.webspace');

        $this->prepareData();
        $this->prepareImportData();
    }

    /**
     * Remove all created Data.
     */
    public function tearDown(): void
    {
        $this->removeImportFile();
        parent::tearDown();
    }

    /**
     * Run tests for language import:
     * - import data
     * - get documents
     * - test document data.
     */
    public function testImport12Xliff(): void
    {
        // run language import
        $importData = [
            'webspaceKey' => 'sulu_io',
            'locale' => 'en',
            'format' => '1.2.xliff',
            'filePath' => $this->path,
        ];

        $import = $this->webspaceImporter->import(
            $importData['webspaceKey'],
            $importData['locale'],
            $importData['filePath'],
            null,
            $importData['format'],
            '',
            false
        );

        // testing imported data
        $loadedDocuments = [];

        /* @var BasePageDocument $document */
        $loadedDocuments[0] = $this->documentManager->find(
            $this->pages[0]->getUuid(),
            'en',
            [
                'type' => 'page',
                'load_ghost_content' => false,
            ]
        );

        /* @var BasePageDocument $document */
        $loadedDocuments[1] = $this->documentManager->find(
            $this->pages[1]->getUuid(),
            'en',
            [
                'type' => 'page',
                'load_ghost_content' => false,
            ]
        );

        // import
        $this->assertEquals($import->successes, 2);

        // structure
        $this->assertEquals('test 0 imported', $loadedDocuments[0]->getTitle());
        $this->assertEquals('test 1 imported', $loadedDocuments[1]->getTitle());

        // path
        $this->assertEquals('/cmf/sulu_io/contents/test-0-imported', $loadedDocuments[0]->getPath());
        $this->assertEquals('/cmf/sulu_io/contents/test-1-imported', $loadedDocuments[1]->getPath());

        // resource segment
        $this->assertEquals('/parent', $loadedDocuments[0]->getResourceSegment());
        $this->assertEquals('/child', $loadedDocuments[1]->getResourceSegment());

        // seo
        $this->assertEquals('', $loadedDocuments[0]->getExtensionsData()->toArray()['seo']['title']);
        $this->assertEquals('SEO Title', $loadedDocuments[1]->getExtensionsData()->toArray()['seo']['title']);

        // excerpt
        $this->assertEquals('Excerpt title', $loadedDocuments[0]->getExtensionsData()->toArray()['excerpt']['title']);
        $this->assertEquals('', $loadedDocuments[1]->getExtensionsData()->toArray()['excerpt']['title']);
    }

    /**
     * Run tests for language "ru" import:
     * - import data
     * - get documents
     * - test document data.
     */
    public function testImport12XliffRU(): void
    {
        // run language import
        // import it to FR (because RU isn't initialized)
        $importData = [
            'webspaceKey' => 'sulu_io',
            'locale' => 'fr',
            'format' => '1.2.xliff',
            'filePath' => $this->pathRU,
        ];

        $import = $this->webspaceImporter->import(
            $importData['webspaceKey'],
            $importData['locale'],
            $importData['filePath'],
            null,
            $importData['format'],
            '',
            false
        );

        // testing imported data
        $loadedDocuments = [];

        /* @var BasePageDocument $document */
        $loadedDocuments[0] = $this->documentManager->find(
            $this->pages[0]->getUuid(),
            'fr',
            [
                'type' => 'page',
                'load_ghost_content' => false,
            ]
        );

        /* @var BasePageDocument $document */
        $loadedDocuments[1] = $this->documentManager->find(
            $this->pages[1]->getUuid(),
            'fr',
            [
                'type' => 'page',
                'load_ghost_content' => false,
            ]
        );

        // import
        $this->assertEquals($import->successes, 2);

        // structure
        $this->assertEquals('привет', $loadedDocuments[0]->getTitle());
        $this->assertEquals('привет привет привет', $loadedDocuments[1]->getTitle());

        // path
        $this->assertEquals('/cmf/sulu_io/contents/parent', $loadedDocuments[0]->getPath());
        $this->assertEquals('/cmf/sulu_io/contents/child', $loadedDocuments[1]->getPath());

        // resource segment
        $this->assertEquals('/privet', $loadedDocuments[0]->getResourceSegment());
        $this->assertEquals('/privet-privet-privet', $loadedDocuments[1]->getResourceSegment());

        // seo
        $this->assertEquals('', $loadedDocuments[0]->getExtensionsData()->toArray()['seo']['title']);
        $this->assertEquals('SEO Title', $loadedDocuments[1]->getExtensionsData()->toArray()['seo']['title']);

        // excerpt
        $this->assertEquals('Excerpt title', $loadedDocuments[0]->getExtensionsData()->toArray()['excerpt']['title']);
        $this->assertEquals('', $loadedDocuments[1]->getExtensionsData()->toArray()['excerpt']['title']);
    }

    /**
     * Run tests for language "block-in-block" import:
     * - import data
     * - get documents
     * - test document data.
     */
    public function testImport12XliffWithBlockInBlock(): void
    {
        // run language import
        // import it to FR (because RU isn't initialized)
        $importData = [
            'webspaceKey' => 'sulu_io',
            'locale' => 'fr',
            'format' => '1.2.xliff',
            'filePath' => $this->pathBlockInBlock,
        ];

        $this->webspaceImporter->import(
            $importData['webspaceKey'],
            $importData['locale'],
            $importData['filePath'],
            null,
            $importData['format'],
            '',
            false
        );

        // testing imported data
        $loadedDocuments = [];

        /* @var BasePageDocument $document */
        $loadedDocuments[0] = $this->documentManager->find(
            $this->pages[0]->getUuid(),
            'fr',
            [
                'type' => 'page',
                'load_ghost_content' => false,
            ]
        );

        $data = $loadedDocuments[0]->getStructure()->toArray();
        $this->assertEquals('Sulu is awesome', $data['title']);
        $this->assertEquals('/sulu-is-awesome', $data['url']);

        $this->assertEquals('block', $data['blocks'][0]['type']);
        $this->assertEquals('innerBlock', $data['blocks'][0]['innerBlocks'][0]['type']);
        $this->assertEquals('For Developers', $data['blocks'][0]['innerBlocks'][0]['title']);
        $this->assertEquals('innerBlock', $data['blocks'][0]['innerBlocks'][1]['type']);
        $this->assertEquals('For Editors', $data['blocks'][0]['innerBlocks'][1]['title']);
        $this->assertEquals('innerBlock', $data['blocks'][0]['innerBlocks'][2]['type']);
        $this->assertEquals('For Marketers', $data['blocks'][0]['innerBlocks'][2]['title']);

        $this->assertEquals('block', $data['blocks'][1]['type']);
        $this->assertEquals('innerBlock', $data['blocks'][1]['innerBlocks'][0]['type']);
        $this->assertEquals('By Developers', $data['blocks'][1]['innerBlocks'][0]['title']);
        $this->assertEquals('innerBlock', $data['blocks'][1]['innerBlocks'][1]['type']);
        $this->assertEquals('By Editors', $data['blocks'][1]['innerBlocks'][1]['title']);
        $this->assertEquals('innerBlock', $data['blocks'][1]['innerBlocks'][2]['type']);
        $this->assertEquals('By Marketers', $data['blocks'][1]['innerBlocks'][2]['title']);

        $this->assertEquals('block2', $data['blocks'][2]['type']);
        $this->assertEquals('Great Tool', $data['blocks'][2]['title']);
    }

    /**
     * Removes the created export.xliff file.
     */
    private function removeImportFile(): void
    {
        try {
            $fs = new Filesystem();

            $fs->remove($this->path);
            $fs->remove($this->pathRU);
            $fs->remove($this->pathBlockInBlock);
        } catch (IOExceptionInterface $e) {
            echo 'An error occurred while creating your directory at ' . $e->getPath();
        }
    }

    /**
     * Creates the export.xliff file and replace the placeholder with the current uuid.
     */
    private function prepareImportData(): void
    {
        $fs = new Filesystem();

        try {
            $fs->copy($this->distPath, $this->path);

            $distContent = \file_get_contents($this->path, true);
            $newContent = \str_replace(
                [
                    '%uuid_page_0%',
                    '%uuid_page_1%',
                ],
                [
                    $this->pages[0]->getUuid(),
                    $this->pages[1]->getUuid(),
                ],
                $distContent
            );

            \file_put_contents($this->path, $newContent);
        } catch (IOExceptionInterface $e) {
            echo 'An error occurred while creating your directory at ' . $e->getPath();
        }

        try {
            $fs->copy($this->distPathRU, $this->pathRU);

            $distContent = \file_get_contents($this->pathRU, true);
            $newContent = \str_replace(
                [
                    '%uuid_page_0%',
                    '%uuid_page_1%',
                ],
                [
                    $this->pages[0]->getUuid(),
                    $this->pages[1]->getUuid(),
                ],
                $distContent
            );

            \file_put_contents($this->pathRU, $newContent);
        } catch (IOExceptionInterface $e) {
            echo 'An error occurred while creating your directory at ' . $e->getPath();
        }

        try {
            $fs->copy($this->distPathBlockInBlock, $this->pathBlockInBlock);

            $distContent = \file_get_contents($this->pathBlockInBlock, true);
            $newContent = \str_replace(
                [
                    '%uuid_page_0%',
                ],
                [
                    $this->pages[0]->getUuid(),
                ],
                $distContent
            );

            \file_put_contents($this->pathBlockInBlock, $newContent);
        } catch (IOExceptionInterface $e) {
            echo 'An error occurred while creating your directory at ' . $e->getPath();
        }
    }

    /**
     * Create the test-pages.
     */
    private function prepareData(): void
    {
        $this->pages[0] = $this->createSimplePage('Parent', '/parent');
        $this->documentManager->publish($this->pages[0], 'en');

        $this->pages[1] = $this->createSimplePage('Child', '/child');
        $this->documentManager->publish($this->pages[1], 'en');

        $this->documentManager->flush();
    }

    /**
     * Creates a simple page.
     *
     * @param string $title
     * @param string $url
     *
     * @return PageDocument
     */
    private function createSimplePage($title, $url)
    {
        /** @var PageDocument $page */
        $page = $this->documentManager->create('page');
        $page->setTitle($title);
        $page->setResourceSegment($url);
        $page->setLocale('en');
        $page->setParent($this->homeDocument);
        $page->setStructureType('simple');
        $page->setExtensionsData(
            [
                'excerpt' => [
                    'title' => '',
                    'description' => '',
                    'categories' => [],
                    'tags' => [],
                ],
            ]
        );

        $this->documentManager->persist($page, 'en');

        return $page;
    }
}
