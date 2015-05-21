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

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use JMS\Serializer\Serializer;
use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Preview\DoctrineCacheProvider;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * @group functional
 * @group preview
 */
class DoctrineCacheProviderTest extends SuluTestCase
{
    /**
     * @var DoctrineCacheProvider
     */
    private $cache;

    /**
     * @var Cache
     */
    private $dataCache;

    /**
     * @var Cache
     */
    private $changesCache;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    protected function setUp()
    {
        parent::initPhpcr();

        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->serializer = $this->getContainer()->get('jms_serializer');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');

        $this->dataCache = new ArrayCache();
        $this->changesCache = new ArrayCache();

        $this->cache = new DoctrineCacheProvider(
            $this->mapper, $this->structureManager, $this->serializer, $this->dataCache, $this->changesCache
        );
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
                    'tag2',
                ),
                'url' => '/news/test',
                'article' => array('Test-1', 'Test-2'),
            ),
            array(
                'title' => 'Testtitle2',
                'tags' => array(
                    'tag1',
                    'tag2',
                ),
                'url' => '/news/test2',
                'article' => array('Test-1', 'Test-2'),
            ),
        );

        $data[0] = $this->mapper->save($data[0], 'overview', 'sulu_io', 'en', 1);
        $data[1] = $this->mapper->save($data[1], 'overview', 'sulu_io', 'en', 1);

        return $data;
    }

    private function getId($userId, $contentUuid, $locale)
    {
        $method = new ReflectionMethod(
            get_class($this->cache), 'getId'
        );

        $method->setAccessible(true);

        return $method->invokeArgs($this->cache, array($userId, $contentUuid, $locale));
    }

    public function testWarmUp()
    {
        // prepare
        $data = $this->prepareData();
        $result = $this->cache->warmUp(1, $data[0]->getUuid(), 'sulu_io', 'en');

        $this->assertEquals('Testtitle', $result->getPropertyValue('title'));
        $this->assertEquals('overview', $result->getOriginTemplate());

        foreach ($result->getProperties(true) as $property) {
            $this->assertEquals($result, $property->getStructure());
        }

        $data = json_decode($this->dataCache->fetch($this->getId(1, $data[0]->getUuid(), 'en')), true);

        $this->assertEquals('Testtitle', json_decode($data['properties']['title']['value']));
        $this->assertEquals('overview', $data['key']);
    }

    public function testSaveStructure()
    {
        // prepare
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'sulu_io', 'en');

        $data[0]->getProperty('title')->setValue('TEST');

        $this->cache->saveStructure($data[0], 1, $data[0]->getUuid(), 'sulu_io', 'en');
        $result = $this->cache->fetchStructure(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertNotEquals(false, $result);

        $this->assertEquals('TEST', $result->getPropertyValue('title'));
        $this->assertEquals('overview', $result->getOriginTemplate());

        $result = json_decode($this->dataCache->fetch($this->getId(1, $data[0]->getUuid(), 'en')), true);
        $this->assertEquals('TEST', json_decode($result['properties']['title']['value']));
        $this->assertEquals('overview', $result['key']);

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/sulu_io/contents/testtitle');
        $this->assertEquals('Testtitle', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));
    }

    public function testSaveExists()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->cache->saveStructure($data[0], 1, $data[0]->getUuid(), 'sulu_io', 'en');

        $data[0]->getProperty('title')->setValue('TEST');

        $this->cache->saveStructure($data[0], 1, $data[0]->getUuid(), 'sulu_io', 'en');
        $result = $this->cache->fetchStructure(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertNotEquals(false, $result);

        $this->assertEquals('TEST', $result->getPropertyValue('title'));
        $this->assertEquals('overview', $result->getOriginTemplate());

        $result = json_decode($this->dataCache->fetch($this->getId(1, $data[0]->getUuid(), 'en')), true);
        $this->assertEquals('TEST', json_decode($result['properties']['title']['value']));
        $this->assertEquals('overview', $result['key']);

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/sulu_io/contents/testtitle');

        $this->assertEquals('Testtitle', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));
    }

    public function testSaveAnotherExists()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->cache->saveStructure($data[1], 1, $data[1]->getUuid(), 'sulu_io', 'en');

        $this->cache->saveStructure($data[0], 1, $data[0]->getUuid(), 'sulu_io', 'en');
        $result = $this->cache->fetchStructure(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertNotEquals(false, $result);

        $result = json_decode($this->dataCache->fetch($this->getId(1, $data[0]->getUuid(), 'en')), true);
        $this->assertEquals('Testtitle', json_decode($result['properties']['title']['value']));
        $this->assertEquals('overview', $result['key']);

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/sulu_io/contents/testtitle');

        $this->assertEquals('Testtitle', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));

        $session = $this->sessionManager->getSession();
        $node = $session->getNode('/cmf/sulu_io/contents/testtitle2');

        $this->assertEquals('Testtitle2', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('overview', $node->getPropertyValue('i18n:en-template'));
    }

    public function testFetchStructure()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'sulu_io', 'en');

        $result = $this->cache->fetchStructure(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals('Testtitle', $result->getPropertyValue('title'));
        $this->assertEquals('overview', $result->getKey());
    }

    public function testFetchNotExists()
    {
        $this->prepareData();

        $result = $this->cache->fetchStructure(1, '123-123-123', 'sulu_io', 'en');
        $this->assertFalse($result);
    }

    public function testFetchAnotherLanguage()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->cache->saveStructure($data[0], 1, $data[0]->getUuid(), 'sulu_io', 'en');

        $result = $this->cache->contains(1, $data[0]->getUuid(), 'sulu_io', 'de');
        $this->assertFalse($result);
    }

    public function testChanges()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $changes = array('title' => array('asdf', 'asdf'), 'article' => array(''));

        $result = $this->cache->saveChanges($changes, 1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals($changes, $result);

        $result = $this->cache->fetchChanges(1, $data[0]->getUuid(), 'sulu_io', 'en', false);
        $this->assertEquals($changes, $result);

        $result = $this->cache->fetchChanges(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals($changes, $result);

        $result = $this->cache->fetchChanges(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals(array(), $result);
    }

    public function testContains()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'sulu_io', 'en');

        $result = $this->cache->contains(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertTrue($result);
    }

    public function testContainsNotExists()
    {
        $data = $this->prepareData();

        $result = $this->cache->contains(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertFalse($result);
    }

    public function testContainsAnotherLanguage()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[0]->getUuid(), 'sulu_io', 'en');

        $result = $this->cache->contains(1, $data[0]->getUuid(), 'sulu_io', 'de');
        $this->assertFalse($result);
    }

    public function testContainsAnotherExists()
    {
        $data = $this->prepareData();
        $this->cache->warmUp(1, $data[1]->getUuid(), 'sulu_io', 'en');

        $result = $this->cache->contains(1, $data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertFalse($result);
    }
}
