<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Preview;

use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Preview\PreviewCacheProvider;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\StructureInterface;

class PreviewCacheProviderTest extends PhpcrTestCase
{
    /**
     * @var PreviewCacheProvider
     */
    private $cache;

    protected function setUp()
    {
        $this->prepareMapper();

        $this->cache = new PreviewCacheProvider($this->mapper, $this->securityContext, $this->sessionManager);
    }

    public function structureCallback()
    {
        $args = func_get_args();
        $structureKey = $args[0];

        if ($structureKey == 'overview') {
            return $this->getStructureMock();
        }

        return null;
    }

    public function getStructureMock()
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure',
            array('overview', 'asdf', 'asdf', 2400)
        );

        $method = new ReflectionMethod(
            get_class($structureMock), 'addChild'
        );

        $method->setAccessible(true);
        $method->invokeArgs(
            $structureMock,
            array(
                new Property(
                    'title', 'title', 'text_line', false, true, 1, 1, array(),
                    array(
                        new PropertyTag('sulu.node.name', 100)
                    )
                )
            )
        );

        $method->invokeArgs(
            $structureMock,
            array(
                new Property(
                    'url',
                    'url',
                    'resource_locator',
                    false,
                    true,
                    1,
                    1,
                    array(),
                    array(new PropertyTag('sulu.rlp', 1))
                )
            )
        );

        return $structureMock;
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareData()
    {
        $data = array(
            array(
                'title' => 'Testtitle',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test',
                'article' => 'Test'
            ),
            array(
                'title' => 'Testtitle2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test2',
                'article' => 'Test'
            )
        );

        $data[0] =  $this->mapper->save($data[0], 'overview', 'default', 'en', 1);
        $data[1] =  $this->mapper->save($data[1], 'overview', 'default', 'en', 1);
        return $data;
    }

    public function testSave()
    {
        $data = $this->prepareData();

        $result = $this->cache->save($data[0]->getUuid(), $data[0], 'default', 'en');
        $this->assertTrue($result);

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/default/temp/1/preview');

        $this->assertEquals('Testtitle', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));
    }

    public function testLoad()
    {
        $data = $this->prepareData();
        $this->cache->save($data[0]->getUuid(), $data[0], 'default', 'en');

        $result = $this->cache->fetch($data[0]->getUuid(), 'default', 'en');
        $this->assertEquals('Testtitle', $result->title);
        $this->assertEquals('overview', $result->getKey());
    }

    public function testLoadNotExists()
    {
        $data = $this->prepareData();

        $result = $this->cache->fetch($data[0]->getUuid(), 'default', 'en');
        $this->assertFalse($result);
    }

    public function testLoadAnotherExists()
    {
        $data = $this->prepareData();
        $this->cache->save($data[1]->getUuid(), $data[1], 'default', 'en');

        $result = $this->cache->fetch($data[0]->getUuid(), 'default', 'en');
        $this->assertFalse($result);
    }

    public function testContains()
    {
        $data = $this->prepareData();
        $this->cache->save($data[0]->getUuid(), $data[0], 'default', 'en');

        $result = $this->cache->contains($data[0]->getUuid(), 'default', 'en');
        $this->assertTrue($result);
    }

    public function testContainsNotExists()
    {
        $data = $this->prepareData();

        $result = $this->cache->contains($data[0]->getUuid(), 'default', 'en');
        $this->assertFalse($result);
    }

    public function testContainsAnotherExists()
    {
        $data = $this->prepareData();
        $this->cache->save($data[1]->getUuid(), $data[1], 'default', 'en');

        $result = $this->cache->contains($data[0]->getUuid(), 'default', 'en');
        $this->assertFalse($result);
    }

}
