<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Repository;

use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Repository\ResourceLocatorRepository;
use Sulu\Bundle\ContentBundle\Repository\ResourceLocatorRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Types\Rlp\Mapper\PhpcrMapper;
use Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy;
use Sulu\Component\PHPCR\PathCleanup;

/**
 * @group functional
 * @group repository
 */
class ResourceLocatorRepositoryTest extends PhpcrTestCase
{
    /**
     * @var ResourceLocatorRepositoryInterface
     */
    private $repository;

    protected function setUp()
    {
        $this->prepareMapper();
        $this->prepareResourceLocatorRepository();
    }

    private function prepareResourceLocatorRepository()
    {
        $strategy = new TreeStrategy(new PhpcrMapper($this->sessionManager, '/cmf/routes'), new PathCleanup());
        $this->repository = new ResourceLocatorRepository(
            $strategy,
            $this->structureManager,
            $this->containerValueMap['sulu.content.type.resource_locator'],
            $this->mapper
        );
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
            '\Sulu\Component\Content\Structure\Page',
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
                    'title', 'title', 'text_line', false, false, 1, 1, array(),
                    array(
                        new PropertyTag('sulu.rlp.part', 100)
                    )
                )
            )
        );

        $method->invokeArgs(
            $structureMock,
            array(
                new Property('url', 'url', 'resource_locator')
            )
        );

        return $structureMock;
    }

    /**
     * @return \Sulu\Component\Content\StructureInterface
     */
    private function prepareGenerateTestData()
    {
        return $this->mapper->save(array('title' => 'asdf', 'url' => '/asdf'), 'overview', 'default', 'en', 1);
    }

    public function testGenerate()
    {
        $structure = $this->prepareGenerateTestData();

        $result = $this->repository->generate(
            array('title' => 'test'),
            $structure->getUuid(),
            null,
            'default',
            'en',
            'overview'
        );

        $this->assertEquals('/asdf/test', $result['resourceLocator']);
    }

    /**
     * @return \Sulu\Component\Content\StructureInterface
     */
    private function prepareHistoryTestData()
    {
        $structure = $this->mapper->save(array('title' => 'asdf-1', 'url' => '/asdf'), 'overview', 'default', 'en', 1);
        sleep(1);
        $structure = $this->mapper->save(
            array('title' => 'asdf-1', 'url' => '/asdf-1'),
            'overview',
            'default',
            'en',
            1,
            true,
            $structure->getUuid()
        );
        sleep(1);
        $structure = $this->mapper->save(
            array('title' => 'asdf-1', 'url' => '/asdf-2'),
            'overview',
            'default',
            'en',
            1,
            true,
            $structure->getUuid()
        );
        return $structure;
    }

    public function testGetHistory()
    {
        $structure = $this->prepareHistoryTestData();

        $result = $this->repository->getHistory($structure->getUuid(), 'default', 'en');

        $this->assertEquals(2, sizeof($result['_embedded']['resourcelocators']));
        $this->assertEquals('/asdf-1', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
        $this->assertEquals('/asdf', $result['_embedded']['resourcelocators'][1]['resourceLocator']);
    }

    public function testRestore()
    {
        $structure = $this->prepareHistoryTestData();

        $result = $this->repository->restore('/asdf', 'default', 'en');

        $this->assertEquals('/asdf', $result['resourceLocator']);

        $result = $this->repository->getHistory($structure->getUuid(), 'default', 'en');

        $this->assertEquals(2, sizeof($result['_embedded']['resourcelocators']));
        $this->assertEquals('/asdf-2', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
        $this->assertEquals('/asdf-1', $result['_embedded']['resourcelocators'][1]['resourceLocator']);
    }

    public function testDelete()
    {
        $structure = $this->prepareHistoryTestData();

        $this->repository->delete('/asdf', 'default', 'en');
        $result = $this->repository->getHistory($structure->getUuid(), 'default', 'en');

        $this->assertEquals(1, sizeof($result['_embedded']['resourcelocators']));
        $this->assertEquals('/asdf-1', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
    }
}
