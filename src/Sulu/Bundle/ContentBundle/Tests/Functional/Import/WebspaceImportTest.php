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

    private function prepareData()
    {
        $this->pages[0] = $this->createPage([
            'title' => 'test 0',
            'uuid' => 'f7df9533-daa0-45b3-ab52-54ca30991bc0'
        ]);
        $this->pages[1] = $this->createPage([
            'title' => 'test 1',
            'uuid' => 'f7df9533-daa0-45b3-ab52-54ca30991bc1'
        ]);

        // TODO: set correct uuid from export-file

        $this->documentManager->persist($this->pages[0], 'de');
        $this->documentManager->flush();

        $this->documentManager->persist($this->pages[1], 'de');
        $this->documentManager->flush();

        var_dump($this->pages[0]->getUuid());
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