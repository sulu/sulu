<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Import;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
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
    private $parent;
    private $pages = [];
    private $webspaceImporter;
    protected $distPath = '/../../app/Resources/import/export.xliff.dist';
    protected $path = '/../../app/Resources/import/export.xliff';

    /**
     * Setup data for import.
     */
    protected function setUp()
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->homeDocument = $this->documentManager->find('/cmf/sulu_io/contents', 'en');
        $this->webspaceImporter = $this->getContainer()->get('sulu_content.import.webspace');

        $this->prepareData();
        $this->prepareImportData();
    }

    /**
     * Remove all created Data.
     */
    public function tearDown()
    {
        $this->removeImportFile();
    }

    /**
     * Run tests for language import:
     * - import data
     * - get documents
     * - test document data.
     */
    public function testImport12Xliff()
    {
        // run language import
        $importData = [
            'webspaceKey' => 'sulu_io',
            'locale' => 'en',
            'format' => '1.2.xliff',
            'filePath' => __DIR__ . $this->path,
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

        /** @var BasePageDocument $document */
        $loadedDocuments[0] = $this->documentManager->find(
            $this->pages[0]->getUuid(),
            'en',
            [
                'type' => 'page',
                'load_ghost_content' => false,
            ]
        );

        /** @var BasePageDocument $document */
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
        $this->assertEquals($loadedDocuments[0]->getTitle(), 'test 0 imported');
        $this->assertEquals($loadedDocuments[1]->getTitle(), 'test 1 imported');

        // path
        $this->assertEquals($loadedDocuments[0]->getPath(), '/cmf/sulu_io/contents/test-0-imported');
        $this->assertEquals($loadedDocuments[1]->getPath(), '/cmf/sulu_io/contents/test-1-imported');

        // seo
        $this->assertEquals($loadedDocuments[0]->getExtensionsData()->toArray()['seo']['title'], '');
        $this->assertEquals($loadedDocuments[1]->getExtensionsData()->toArray()['seo']['title'], 'SEO Title');

        // excerpt
        $this->assertEquals($loadedDocuments[0]->getExtensionsData()->toArray()['excerpt']['title'], 'Excerpt title');
        $this->assertEquals($loadedDocuments[1]->getExtensionsData()->toArray()['excerpt']['title'], '');
    }

    /**
     * Removes the created export.xliff file.
     */
    private function removeImportFile()
    {
        try {
            $fs = new Filesystem();

            $fs->remove(__DIR__ . $this->path);
        } catch (IOExceptionInterface $e) {
            echo 'An error occurred while creating your directory at ' . $e->getPath();
        }
    }

    /**
     * Creates the export.xliff file and replace the placeholder with the current uuid.
     */
    private function prepareImportData()
    {
        $fs = new Filesystem();

        try {
            $fs->copy(__DIR__ . $this->distPath, __DIR__ . $this->path);

            $distContent = file_get_contents(__DIR__ . $this->path, true);
            $newContent = str_replace([
                '%uuid_page_0%',
                '%uuid_page_1%',
            ], [
                $this->pages[0]->getUuid(),
                $this->pages[1]->getUuid(),
            ], $distContent);

            file_put_contents(__DIR__ . $this->path, $newContent);
        } catch (IOExceptionInterface $e) {
            echo 'An error occurred while creating your directory at ' . $e->getPath();
        }
    }

    /**
     * Create the test-pages.
     */
    private function prepareData()
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
        $page->setExtensionsData([
            'excerpt' => [
                'title' => '',
                'description' => '',
                'categories' => [],
                'tags' => [],
            ],
        ]);

        $this->documentManager->persist($page, 'en');

        return $page;
    }
}
