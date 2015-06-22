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
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\PathCleanup;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;

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

    /**
     * @var Webspace
     */
    private $webspace;

    protected function setUp()
    {
        $this->prepareMapper();
        $this->prepareResourceLocatorRepository();
    }

    /**
     * prepares webspace manager.
     */
    protected function prepareWebspaceManager()
    {
        if ($this->webspaceManager === null) {
            $this->webspaceManager = $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');

            $this->webspace = new Webspace();
            $locale = new Localization();
            $locale->setLanguage('de');
            $locale->setDefault(true);
            $this->webspace->addLocalization($locale);
            $portal = new Portal();
            $portal->addLocalization($locale);
            $this->webspace->addPortal($portal);

            $this->webspaceManager->expects($this->any())->method('findWebspaceByKey')->will(
                $this->returnValue($this->webspace)
            );
        }
    }

    private function prepareResourceLocatorRepository()
    {
        $strategy = new TreeStrategy(
            new PhpcrMapper($this->sessionManager, '/cmf/routes'),
            new PathCleanup(),
            $this->structureManager,
            $this->contentTypeManager,
            $this->nodeHelper
        );
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

        return;
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
                        new PropertyTag('sulu.rlp.part', 100),
                    )
                ),
            )
        );

        $method->invokeArgs(
            $structureMock,
            array(
                new Property('url', 'url', 'resource_locator', false, false, 1, 1, array(),
                    array(
                        new PropertyTag('sulu.rlp', 100),
                    )),
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

        $result = $this->repository->restore('/asdf', 1, 'default', 'en');

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
