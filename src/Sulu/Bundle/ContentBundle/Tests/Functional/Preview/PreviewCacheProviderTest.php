<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Preview\Preview;

use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Preview\PhpcrCacheProvider;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Navigation;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Webspace;

/**
 * @group functional
 * @group preview
 */
class PhpcrCacheProviderTest extends PhpcrTestCase
{
    /**
     * @var PhpcrCacheProvider
     */
    private $cache;

    protected function setUp()
    {
        $this->prepareMapper();

        $this->cache = new PhpcrCacheProvider($this->mapper, $this->sessionManager);
    }

    protected function prepareWebspaceManager()
    {
        if ($this->webspaceManager === null) {
            $webspace = new Webspace();
            $en = new Localization();
            $en->setLanguage('en');
            $en_us = new Localization();
            $en_us->setLanguage('en');
            $en_us->setCountry('us');
            $en_us->setParent($en);
            $en->addChild($en_us);

            $de = new Localization();
            $de->setLanguage('de');
            $de_at = new Localization();
            $de_at->setLanguage('de');
            $de_at->setCountry('at');
            $de_at->setParent($de);
            $de->addChild($de_at);

            $theme = new Theme();
            $theme->setKey('test');
            $webspace->setTheme($theme);

            $es = new Localization();
            $es->setLanguage('es');

            $webspace->addLocalization($en);
            $webspace->addLocalization($de);
            $webspace->addLocalization($es);

            $webspace->setNavigation(new Navigation(array(new NavigationContext('main', array()))));

            $this->webspaceManager = $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
            $this->webspaceManager->expects($this->any())
                ->method('findWebspaceByKey')
                ->will($this->returnValue($webspace));
        }
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

    public function testWarmUp()
    {
        // prepare
        $data = $this->prepareData();
        $result = $this->cache->warmUp(1, $data[0]->getUuid(), 'default', 'en');

        $this->assertEquals('Testtitle', $result->getPropertyValue('title'));
        $this->assertEquals('overview', $result->getOriginTemplate());

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/default/temp/1/preview');

        $this->assertEquals('Testtitle', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));
    }

    public function testSaveStructure()
    {
        // prepare
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'default', 'en');

        $data[0]->getProperty('title')->setValue('TEST');

        $result = $this->cache->saveStructure($data[0], 1, $data[0]->getUuid(), 'default', 'en');
        $this->assertNotEquals(false, $result);

        $this->assertEquals('TEST', $result->getPropertyValue('title'));
        $this->assertEquals('overview', $result->getOriginTemplate());

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/default/temp/1/preview');

        $this->assertEquals('TEST', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/default/contents/testtitle');

        $this->assertEquals('Testtitle', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));
    }

    public function testSaveExists()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'default', 'en');
        $this->cache->saveStructure($data[0], 1, $data[0]->getUuid(), 'default', 'en');

        $data[0]->getProperty('title')->setValue('TEST');

        $result = $this->cache->saveStructure($data[0], 1, $data[0]->getUuid(), 'default', 'en');
        $this->assertNotEquals(false, $result);

        $this->assertEquals('TEST', $result->getPropertyValue('title'));
        $this->assertEquals('overview', $result->getOriginTemplate());

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/default/temp/1/preview');

        $this->assertEquals('TEST', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/default/contents/testtitle');

        $this->assertEquals('Testtitle', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));
    }

    public function testSaveAnotherExists()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'default', 'en');
        $this->cache->saveStructure($data[1], 1, $data[1]->getUuid(), 'default', 'en');

        $result = $this->cache->saveStructure($data[0], 1, $data[0]->getUuid(), 'default', 'en');
        $this->assertNotEquals(false, $result);

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/default/temp/1/preview');

        $this->assertEquals('Testtitle', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/default/contents/testtitle');

        $this->assertEquals('Testtitle', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/default/contents/testtitle2');

        $this->assertEquals('Testtitle2', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));
    }

    public function testFetchStructure()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'default', 'en');

        $result = $this->cache->fetchStructure(1, 'default', 'en');
        $this->assertEquals('Testtitle', $result->getPropertyValue('title'));
        $this->assertEquals('overview', $result->getKey());
    }

    public function testFetchNotExists()
    {
        $this->prepareData();

        $result = $this->cache->fetchStructure(1, 'default', 'en');
        $this->assertFalse($result);
    }

    public function testFetchAnotherLanguage()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'default', 'en');
        $this->cache->saveStructure($data[0], 1, $data[0]->getUuid(), 'default', 'en');

        $result = $this->cache->contains(1, $data[0]->getUuid(), 'default', 'de');
        $this->assertFalse($result);
    }

    public function testChanges()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'default', 'en');
        $changes = array('title' => array('asdf', 'asdf'), 'article' => array(''));

        $result = $this->cache->saveChanges($changes, 1, 'default');
        $this->assertEquals($changes, $result);

        $result = $this->cache->fetchChanges(1, 'default', false);
        $this->assertEquals($changes, $result);

        $result = $this->cache->fetchChanges(1, 'default');
        $this->assertEquals($changes, $result);

        $result = $this->cache->fetchChanges(1, 'default');
        $this->assertEquals(array(), $result);
    }

    public function testContains()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'default', 'en');

        $result = $this->cache->contains(1, $data[0]->getUuid(), 'default', 'en');
        $this->assertTrue($result);
    }

    public function testContainsNotExists()
    {
        $data = $this->prepareData();

        $result = $this->cache->contains(1, $data[0]->getUuid(), 'default', 'en');
        $this->assertFalse($result);
    }

    public function testContainsAnotherLanguage()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'default', 'en');

        $result = $this->cache->contains(1, $data[0]->getUuid(), 'default', 'de');
        $this->assertFalse($result);
    }

    public function testContainsAnotherExists()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[1]->getUuid(), 'default', 'en');

        $result = $this->cache->contains(1, $data[0]->getUuid(), 'default', 'en');
        $this->assertFalse($result);
    }

}
