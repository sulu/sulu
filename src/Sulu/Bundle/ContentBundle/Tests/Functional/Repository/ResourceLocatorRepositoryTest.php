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
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyTag;
use Sulu\Component\Content\Types\Rlp\Mapper\PhpcrMapper;
use Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy;
use Sulu\Component\PHPCR\PathCleanup;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * @group functional
 * @group repository
 */
class ResourceLocatorRepositoryTest extends SuluTestCase
{
    /**
     * @var ResourceLocatorRepositoryInterface
     */
    private $repository;

    /**
     * @var Webspace
     */
    private $webspace;

    protected function setUp()
    {
        $this->initPhpcr();
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->repository = $this->getContainer()->get('sulu_content.rl_repository');
    }

    /**
     * @return \Sulu\Component\Content\Compat\StructureInterface
     */
    private function prepareGenerateTestData()
    {
        return $this->mapper->save(
            array(
                'title' => 'test',
                'url' => '/test'
            ), 
            'overview',
            'sulu_io',
            'en',
            1
        );
    }

    public function testGenerate()
    {
        $structure = $this->prepareGenerateTestData();

        $result = $this->repository->generate(
            array(
                'title' => 'test'
            ),
            $structure->getUuid(),
            null,
            'sulu_io',
            'en',
            'overview'
        );

        $this->assertEquals('/test/test', $result['resourceLocator']);
    }

    /**
     * @return \Sulu\Component\Content\Compat\StructureInterface
     */
    private function prepareHistoryTestData()
    {
        $structure = $this->mapper->save(
            array(
                'title' => 'test-1', 
                'url' => '/test'
            ), 
            'overview',
            'sulu_io',
            'en',
            1
        );
        sleep(1);

        $structure = $this->mapper->save(
            array(
                'title' => 'test-1',
                'url' => '/test-1'
            ),
            'overview',
            'sulu_io',
            'en',
            1,
            true,
            $structure->getUuid()
        );

        sleep(1);

        $structure = $this->mapper->save(
            array(
                'title' => 'test-1',
                'url' => '/test-2'
            ),
            'overview',
            'sulu_io',
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

        $result = $this->repository->getHistory($structure->getUuid(), 'sulu_io', 'en');

        $this->assertEquals(2, sizeof($result['_embedded']['resourcelocators']));
        $this->assertEquals('/test-1', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
        $this->assertEquals('/test', $result['_embedded']['resourcelocators'][1]['resourceLocator']);
    }

    public function testRestore()
    {
        $structure = $this->prepareHistoryTestData();

        $result = $this->repository->restore('/test', 1, 'sulu_io', 'en');

        $this->assertEquals('/test', $result['resourceLocator']);

        $result = $this->repository->getHistory($structure->getUuid(), 'sulu_io', 'en');

        $this->assertEquals(2, sizeof($result['_embedded']['resourcelocators']));
        $this->assertEquals('/test-2', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
        $this->assertEquals('/test-1', $result['_embedded']['resourcelocators'][1]['resourceLocator']);
    }

    public function testDelete()
    {
        $structure = $this->prepareHistoryTestData();

        $this->repository->delete('/test', 'sulu_io', 'en');
        $result = $this->repository->getHistory($structure->getUuid(), 'sulu_io', 'en');

        $this->assertEquals(1, sizeof($result['_embedded']['resourcelocators']));
        $this->assertEquals('/test-1', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
    }
}
