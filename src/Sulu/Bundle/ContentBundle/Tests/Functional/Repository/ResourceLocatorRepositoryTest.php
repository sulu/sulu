<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Repository;

use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Repository\ResourceLocatorRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

/**
 * @group functional
 * @group repository
 */
class ResourceLocatorRepositoryTest extends SuluTestCase
{
    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var ResourceLocatorRepositoryInterface
     */
    private $repository;

    protected function setUp()
    {
        $this->initPhpcr();
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->session = $this->getContainer()->get('doctrine_phpcr.default_session');
        $this->repository = $this->getContainer()->get('sulu_content.rl_repository');
    }

    /**
     * @return \Sulu\Component\Content\Compat\StructureInterface
     */
    private function prepareGenerateTestData()
    {
        return $this->mapper->save(
            [
                'title' => 'test',
                'url' => '/test',
            ],
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
            [
                'title' => 'test',
            ],
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
            [
                'title' => 'test-1',
                'url' => '/test',
            ],
            'overview',
            'sulu_io',
            'en',
            1
        );
        sleep(1);

        $structure = $this->mapper->save(
            [
                'title' => 'test-1',
                'url' => '/test-1',
            ],
            'overview',
            'sulu_io',
            'en',
            1,
            true,
            $structure->getUuid()
        );

        sleep(1);

        $structure = $this->mapper->save(
            [
                'title' => 'test-1',
                'url' => '/test-2',
            ],
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

        $this->assertEquals(2, count($result['_embedded']['resourcelocators']));
        $this->assertEquals('/test-1', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
        $this->assertEquals('/test', $result['_embedded']['resourcelocators'][1]['resourceLocator']);
    }

    public function testRestore()
    {
        $structure = $this->prepareHistoryTestData();

        $result = $this->repository->restore('/test', 1, 'sulu_io', 'en');

        $this->assertEquals('/test', $result['resourceLocator']);

        $result = $this->repository->getHistory($structure->getUuid(), 'sulu_io', 'en');

        $this->assertEquals(2, count($result['_embedded']['resourcelocators']));
        $this->assertEquals('/test-2', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
        $this->assertEquals('/test-1', $result['_embedded']['resourcelocators'][1]['resourceLocator']);
    }

    public function testDelete()
    {
        $structure = $this->prepareHistoryTestData();

        $this->repository->delete('/test', 'sulu_io', 'en');
        $this->session->save();
        $result = $this->repository->getHistory($structure->getUuid(), 'sulu_io', 'en');

        $this->assertEquals(1, count($result['_embedded']['resourcelocators']));
        $this->assertEquals('/test-1', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
    }
}
