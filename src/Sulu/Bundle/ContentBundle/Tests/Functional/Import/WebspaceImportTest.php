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

    protected function setUp()
    {
        parent::initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->parent = $this->documentManager->find('/cmf/sulu_io/contents', 'de');
        $this->webspaceImporter = $this->getContainer()->get('sulu_content.import.webspace');
    }

    public function testImport12Xliff()
    {
        $this->prepareData();
        $this->prepareImportData();

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

        $this->removeImportFile();
    }

    private function removeImportFile()
    {
        $fs = new Filesystem();
        $path = './src/Sulu/Bundle/ContentBundle/Tests/app/Resources/import/export.xliff';

        $fs->remove($path);
    }

    private function prepareImportData()
    {
        $fs = new Filesystem();
        $distPath = './src/Sulu/Bundle/ContentBundle/Tests/app/Resources/import/export.xliff.dist';
        $path = './src/Sulu/Bundle/ContentBundle/Tests/app/Resources/import/export.xliff';

        try {
            $fs->copy($distPath, $path);

            $distContent = file_get_contents($path, true);
            $newContent = str_replace('%uuid_page_0%', $this->pages[0]->getUuid(), $distContent);
            $newContent = str_replace('%uuid_page_1%', $this->pages[1]->getUuid(), $distContent);

            file_put_contents($path, $newContent);
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while copy distfile ".$e->getPath();
        }
    }

    private function prepareData()
    {
        $this->pages[0] = $this->createPage([
            'title' => 'test 0'
        ]);
        $this->pages[1] = $this->createPage([
            'title' => 'test 1'
        ]);

        // TODO: set correct uuid from export-file

        $this->documentManager->persist($this->pages[0], 'de');
        $this->documentManager->flush();

        $this->documentManager->persist($this->pages[1], 'de');
        $this->documentManager->flush();
    }

    /**
     * @param $data
     * @return PageDocument
     */
    private function createPage($data)
    {
        $page = new PageDocument();

        $uuidReflection = new \ReflectionProperty(PageDocument::class, 'uuid');
        $uuidReflection->setAccessible(true);
        $uuidReflection->setValue($page, 1);

        $page->setTitle($data['title']);
        $page->setParent($this->parent);
        $page->setStructureType('overview');
        $page->setResourceSegment('/foo');
        $page->getStructure()->bind($data, true);

        return $page;
    }
}

?>