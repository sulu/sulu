<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Behat;

use Behat\Behat\Context\SnippetAcceptingContext;
use Sulu\Bundle\TestBundle\Behat\BaseContext;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * Base context class for Structure based feature contexts.
 */
class BaseStructureContext extends BaseContext implements SnippetAcceptingContext
{
    /**
     * @var string Temporary structure template paths
     */
    protected $templatePaths = [];

    /**
     * Remove the generated templates after the scenario has completed.
     *
     * @AfterScenario
     */
    public function removeTempStructureTemplates()
    {
        if (!$this->templatePaths) {
            return;
        }

        foreach (array_keys($this->templatePaths) as $templatePath) {
            unlink($templatePath);
        }

        $this->templatePaths = [];
    }

    /**
     * Create structures of the given type using the given data.
     *
     * Data should be of the form:
     *
     * array(
     *     array(
     *        'title' => null,
     *        'url' => null,
     *        'template' => null,
     *        'locale' => 'de',
     *        'data' => '{}',
     *        'parent' => null,
     *     ),
     * ),
     *
     * @param string $type
     * @param array  $data
     */
    protected function createStructures($type, $data)
    {
        foreach ($data as $structureData) {
            $structureData = array_merge([
                'title' => null,
                'url' => null,
                'template' => null,
                'locale' => 'de',
                'data' => '{}',
                'parent' => null,
                'published' => true,
            ], $structureData);

            $parentUuid = null;

            if ($structureData['parent']) {
                $parentPath = $type === 'page' ? '/cmf/sulu_io/contents' : '/cmf/snippets';
                $parentNode = $this->getPhpcrSession()->getNode($parentPath . $structureData['parent']);
                $parentUuid = $parentNode->getIdentifier();
            }

            $propertyData = [];
            if ($structureData['data']) {
                $propertyData = json_decode($structureData['data'], true);
                if (null === $propertyData) {
                    throw new \Exception('Could not decode json string: "%s"', $structureData['data']);
                }
            }

            $document = $this->getDocumentManager()->create($type);
            $document->setStructureType($structureData['template']);
            $document->setTitle($structureData['title']);
            $document->getStructure()->bind($propertyData);
            $document->setWorkflowStage(WorkflowStage::PUBLISHED);

            if ($document instanceof ResourceSegmentBehavior) {
                $document->setResourceSegment($structureData['url']);
            }

            $persistOptions = [];
            if ($type === 'page') {
                if ($parentUuid) {
                    $document->setParent($this->getDocumentManager()->find($parentUuid, 'de'));
                } else {
                    $persistOptions = ['parent_path' => '/cmf/sulu_io/contents'];
                }
            }

            $this->getDocumentManager()->persist($document, 'de', $persistOptions);

            if ($structureData['published']) {
                $this->getDocumentManager()->publish($document, 'de');
            }

            $this->getDocumentManager()->flush();
        }
    }

    /**
     * Returns the document manager.
     *
     * @return DocumentManagerInterface
     */
    protected function getDocumentManager()
    {
        return $this->getService('sulu_document_manager.document_manager');
    }

    /**
     * Create a structure and install a structure template of
     * then given type (page|structure), name with the given template
     * contents.
     *
     * Will choose the directory to install the template into based upon
     * the default directory for the given type.
     *
     * These installed templates will be removed after the scenario has
     * been completed.
     *
     * @param string $type     Type of the structure template
     * @param string $name     Name of the structure template to create
     * @param string $template The contents of the XML template
     */
    protected function createStructureTemplate($type, $name, $template)
    {
        $paths = $this->getContainer()->getParameter('sulu.content.structure.paths');

        if (!isset($paths[$type])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown structure type, "%s" in behat test',
                $type
            ));
        }

        $paths = $paths[$type];

        if (count($paths) == 0) {
            throw new \Exception(sprintf(
                'No "%s" paths configured in container parameter "sulu.content.structure.paths',
                $type
            ));
        }

        if (isset($paths[0])) {
            $path = $paths[0];
        } else {
            $path = reset($paths);
        }

        $templatePath = $path['path'] . '/' . $name . '.xml';
        $this->templatePaths[$templatePath] = true;
        file_put_contents($templatePath, $template);
    }
}
