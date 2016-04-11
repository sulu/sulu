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
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperRequest;

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

            $request = ContentMapperRequest::create()
                ->setTemplateKey($structureData['template'])
                ->setType($type)
                ->setUserId($this->getUserId())
                ->setLocale('de')
                ->setData(array_merge([
                    'title' => $structureData['title'],
                    'url' => $structureData['url'],
                ], $propertyData));

            if ($type === 'page') {
                $request->setWebspaceKey('sulu_io');
                $request->setState(StructureInterface::STATE_PUBLISHED);

                if ($parentUuid) {
                    $request->setParentUuid($parentUuid);
                }
            }

            $this->getContentMapper()->saveRequest($request);
        }
    }

    /**
     * Return the content mapper.
     *
     * @return Sulu\Component\Content\Mapper\ContentMapperInterface
     */
    protected function getContentMapper()
    {
        return $this->getService('sulu.content.mapper');
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
