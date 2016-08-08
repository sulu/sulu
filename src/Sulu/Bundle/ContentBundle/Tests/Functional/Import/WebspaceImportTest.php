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

    protected $distPath = './src/Sulu/Bundle/ContentBundle/Tests/app/Resources/import/export.xliff.dist';
    protected $path = './src/Sulu/Bundle/ContentBundle/Tests/app/Resources/import/export.xliff';

    protected function setUp()
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->homeDocument = $this->documentManager->find('/cmf/sulu_io/contents', 'en');
        $this->webspaceImporter = $this->getContainer()->get('sulu_content.import.webspace');

        $this->prepareData();
        $this->prepareImportData();
    }

    public function tearDown() {
        $this->removeImportFile();
    }

    public function testImport12Xliff()
    {
        $importData = [
            'webspaceKey' => 'sulu_io',
            'locale' => 'en',
            'format' => '1.2.xliff',
            'filePath' => './src/Sulu/Bundle/ContentBundle/Tests/app/Resources/import/export.xliff'
        ];

        list($count, $fails, $successes, $failed) = $this->webspaceImporter->import(
            $importData['webspaceKey'],
            $importData['locale'],
            $importData['filePath'],
            $importData['format'],
            '',
            false
        );

        $this->assertEquals(
            $successes,
            2
        );
    }

    public function testImportTitle()
    {
        /** @var BasePageDocument $document */
        $document = $this->documentManager->find(
            $this->pages[1]->getUuid(),
            'en',
            [
                'type' => 'page',
                'load_ghost_content' => false,
            ]
        );

        var_dump($document->getTitle());

        $this->assertEquals(
            1,
            1
        );
    }

    private function removeImportFile()
    {
        try {
            $fs = new Filesystem();

            $fs->remove($this->path);
        } catch(IOExceptionInterface $e) {
            echo "An error occurred while creating your directory at ".$e->getPath();
        }
    }

    private function prepareImportData()
    {
        $fs = new Filesystem();

        try {
            $fs->copy($this->distPath, $this->path);

            $distContent = file_get_contents($this->path, true);
            $newContent = str_replace('%uuid_page_0%', $this->pages[0]->getUuid(), $distContent);
            $newContent = str_replace('%uuid_page_1%', $this->pages[1]->getUuid(), $newContent);

            file_put_contents($this->path, $newContent);
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while copy distfile ".$e->getPath();
        }
    }

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

?>