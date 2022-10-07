<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Import;

use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Snippet\Import\SnippetImportInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests for the Snippet Import class.
 */
class SnippetImportTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SnippetImportInterface
     */
    private $snippetImporter;

    /**
     * @var SnippetDocument[]
     */
    private $snippets = [];

    /**
     * @var string
     */
    protected $distPath;

    /**
     * @var string
     */
    protected $path;

    /**
     * Setup data for import.
     */
    protected function setUp(): void
    {
        $this->distPath = __DIR__ . '/../../Fixtures/import/export.xliff.dist';
        $this->path = __DIR__ . '/../../Fixtures/import/export.xliff';

        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->snippetImporter = $this->getContainer()->get('sulu_snippet.import.snippet');

        $this->prepareData();
        $this->prepareImportData();
    }

    /**
     * Remove all created Data.
     */
    public function tearDown(): void
    {
        $this->removeImportFile();
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

        $import = $this->snippetImporter->import(
            $importData['locale'],
            $importData['filePath'],
            null,
            $importData['format']
        );

        // testing imported data
        $loadedDocuments = [];

        /* @var BasePageDocument $document */
        $loadedDocuments[0] = $this->documentManager->find(
            $this->snippets[0]->getUuid(),
            'en',
            [
                'type' => 'snippet',
                'load_ghost_content' => false,
            ]
        );

        $loadedDocuments[1] = $this->documentManager->find(
            $this->snippets[1]->getUuid(),
            'en',
            [
                'type' => 'snippet',
                'load_ghost_content' => false,
            ]
        );

        $this->assertEquals($loadedDocuments[0]->getTitle(), 'Title1 imported');
        $this->assertEquals($loadedDocuments[1]->getTitle(), 'Title2 imported');

        $this->assertEquals($loadedDocuments[0]->getStructure()->getProperty('description')->getValue(), '<p>Description1 imported</p>');
        $this->assertEquals($loadedDocuments[1]->getStructure()->getProperty('description')->getValue(), '<p>Description2 imported</p>');
    }

    /**
     * Removes the created export.xliff file.
     */
    private function removeImportFile(): void
    {
        try {
            $fs = new Filesystem();

            $fs->remove($this->path);
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
            $newContent = \str_replace([
                '%uuid_snippet_0%',
                '%uuid_snippet_1%',
            ], [
                $this->snippets[0]->getUuid(),
                $this->snippets[1]->getUuid(),
            ], $distContent);

            \file_put_contents($this->path, $newContent);
        } catch (IOExceptionInterface $e) {
            echo 'An error occurred while creating your directory at ' . $e->getPath();
        }
    }

    /**
     * Create the test-pages.
     */
    private function prepareData(): void
    {
        $this->snippets[0] = $this->documentManager->create('snippet');
        $this->snippets[0]->setStructureType('hotel');
        $this->snippets[0]->setTitle('Title1');
        $this->snippets[0]->getStructure()->bind([
            'description' => 'Description1',
        ]);
        $this->snippets[0]->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($this->snippets[0], 'en');
        $this->documentManager->flush();

        $this->snippets[1] = $this->documentManager->create('snippet');
        $this->snippets[1]->setStructureType('hotel');
        $this->snippets[1]->setTitle('Title2');
        $this->snippets[1]->getStructure()->bind([
            'description' => 'Description2',
        ]);
        $this->snippets[1]->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($this->snippets[1], 'en');
        $this->documentManager->flush();
    }
}
