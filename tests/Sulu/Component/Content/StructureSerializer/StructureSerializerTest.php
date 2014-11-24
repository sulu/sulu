<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\StructureSerializer;

use DateTime;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Content\StructureManagerInterface;

class StructureSerializerTest extends SuluTestCase
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var StructureSerializer
     */
    private $serializer;

    protected function setUp()
    {
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->serializer = new StructureSerializer($this->structureManager);
    }

    private function preparePage()
    {
        $date = new DateTime();
        /** @var Page $structure */
        $structure = $this->structureManager->getStructure('structure_serializer');

        $structure->setUuid('123-123-123');
        $structure->setChanger(1);
        $structure->setChanged($date);
        $structure->setCreator(1);
        $structure->setCreated($date);

        $structure->setPath('/');
        $structure->setOriginTemplate('structure_serializer');

        $structure->getProperty('title')->setValue('Test-Title');
        $structure->getProperty('url')->setValue('/test-title');

        /** @var BlockProperty $block */
        $block = $structure->getProperty('block');
        $block->setValue(
            array(
                array('type' => 'test-1', 'title' => 'Test-Title-1'),
                array('type' => 'test-2', 'name' => 'Test-Title-1')
            )
        );

        return $structure;
    }

    public function testSerializePage()
    {
        $structure = $this->preparePage();
        $date = $structure->getChanged();

        $result = $this->serializer->serialize($structure);

        $this->assertEquals(
            array(
                'id' => '123-123-123',
                'nodeType' => 1,
                'internal' => false,
                'enabledShadowLanguages' => array(),
                'concreteLanguages' => array(),
                'shadowOn' => false,
                'shadowBaseLanguage' => false,
                'template' => 'structure_serializer',
                'hasSub' => false,
                'creator' => 1,
                'changer' => 1,
                'created' => $date,
                'changed' => $date,
                'title' => 'Test-Title',
                'url' => '/test-title',
                'block' => array(
                    array('type' => 'test-1', 'title' => 'Test-Title-1'),
                    array('type' => 'test-2', 'name' => 'Test-Title-1')
                ),
                'path' => '/',
                'nodeState' => 1,
                'publishedState' => false,
                'navContexts' => array(),
                'originTemplate' => 'structure_serializer',
                'published' => false,
                'ext' => array()
            ),
            $result
        );
    }

    public function testDeserializePage()
    {
        $structure = $this->preparePage();
        $date = $structure->getChanged();
        $data = $structure->toArray(true);

        $result = $this->serializer->deserialize($data);

        $this->assertEquals('123-123-123', $result->getUuid());
        $this->assertEquals(1, $result->getChanger());
        $this->assertEquals(1, $result->getCreator());
        $this->assertEquals($date, $result->getChanged());
        $this->assertEquals($date, $result->getCreated());

        $this->assertEquals('/', $result->getPath());
        $this->assertEquals('structure_serializer', $result->getOriginTemplate());

        $this->assertEquals('Test-Title', $result->getPropertyValue('title'));
        $this->assertEquals('/test-title', $result->getPropertyValue('url'));
        $this->assertEquals(
            array(
                array('type' => 'test-1', 'title' => 'Test-Title-1'),
                array('type' => 'test-2', 'name' => 'Test-Title-1')
            ),
            $result->getPropertyValue('block')
        );
    }
}
