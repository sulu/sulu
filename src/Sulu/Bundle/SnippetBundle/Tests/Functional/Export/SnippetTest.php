<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Export;

use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * Tests for the Webspace Export class.
 */
class SnippetTest extends SuluTestCase
{
    /**
     * @var SnippetExportInterface
     */
    private $snippetExporter;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var int
     */
    private $creator;

    protected function setUp()
    {
        parent::initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->snippetExporter = $this->getContainer()->get('sulu_snippet.export.snippet');
    }

    public function test12Xliff()
    {
        $snippets = $this->prepareData();
        $exportData = $this->snippetExporter->getExportData();

        unset($exportData['snippetData'][0]['uuid']);
        unset($exportData['snippetData'][1]['uuid']);

        // Snippet 1 test
        $this->assertEquals(
            $exportData['snippetData'][0],
            $this->getExportResultData($this->snippet1)
        );

        // Snippet 2 test
        $this->assertEquals(
            $exportData['snippetData'][1],
            $this->getExportResultData($this->snippet2)
        );
    }

    /**
     * Create snippets for test.
     */
    protected function prepareData()
    {
        $this->snippet1 = $this->documentManager->create('snippet');
        $this->snippet1->setStructureType('hotel');
        $this->snippet1->setTitle('ElePHPant1');
        $this->snippet1->getStructure()->bind([
            'description' => 'Elephants are large mammals of the family Elephantidae and the order Proboscidea.1',
        ]);
        $this->snippet1->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($this->snippet1, 'en');
        $this->documentManager->flush();

        $this->snippet2 = $this->documentManager->create('snippet');
        $this->snippet2->setStructureType('hotel');
        $this->snippet2->setTitle('ElePHPant2');
        $this->snippet2->getStructure()->bind([
            'description' => 'Elephants are large mammals of the family Elephantidae and the order Proboscidea.2',
        ]);
        $this->snippet2->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($this->snippet2, 'en');
        $this->documentManager->flush();
    }

    /**
     * @param SnippetDocument $snippet
     */
    protected function getExportResultData($snippet)
    {
        return [
            'locale' => $snippet->getLocale(),
            'content' => [
                'title' => [
                    'name' => 'title',
                    'type' => 'text_line',
                    'options' => [
                        'translate' => true,
                    ],
                    'value' => $snippet->getTitle(),
                ],
                'description' => [
                    'name' => 'description',
                    'type' => 'text_editor',
                    'options' => [
                        'translate' => true,
                    ],
                    'value' => $snippet->getStructure()->getProperty('description')->getValue(),
                ],
            ],
        ];
    }

    /**
     * Creates and returns a property-array.
     *
     * @param PropertyMetadata $property
     * @param PropertyValue $propertyValue
     *
     * @return array
     */
    protected function getPropertyData(PropertyMetadata $property, $propertyValue)
    {
        return $this->createProperty(
            $property->getName(),
            $this->exportManager->export($property->getType(), $propertyValue),
            $this->exportManager->getOptions($property->getType(), $this->format),
            $property->getType()
        );
    }
}
