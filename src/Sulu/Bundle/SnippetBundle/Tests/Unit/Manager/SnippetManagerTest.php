<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Manager;

use ReflectionMethod;
use Sulu\Bundle\SnippetBundle\Manager\SnippetManager;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Structure\Snippet;
use Sulu\Component\Content\Structure;

class SnippetManagerTest extends PhpcrTestCase
{
    /**
     * @var SnippetManager
     */
    private $manager;

    protected function setUp()
    {
        $this->prepareMapper();

        $this->manager = new SnippetManager($this->mapper, $this->sessionManager);
    }

    public function snippetCallback()
    {
        $args = func_get_args();
        $key = $args[0];

        return $this->getSnippetMock($key);
    }

    public function getSnippetMock($name)
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure\Snippet',
            array($name, 'asdf', 'asdf', 2400)
        );

        $method = new ReflectionMethod(
            get_class($structureMock), 'addChild'
        );

        $method->setAccessible(true);
        $method->invokeArgs(
            $structureMock,
            array(
                new Property(
                    'name',
                    '',
                    'text_line',
                    false,
                    true,
                    1,
                    1,
                    array(),
                    array(new PropertyTag('sulu.node.name', 10))
                )
            )
        );

        return $structureMock;
    }

    public function testGetAll()
    {
        $snippetNode = $this->sessionManager->getSnippetNode();

        $request = ContentMapperRequest::create()
            ->setUserId(1)
            ->setLocale('en')
            ->setData(array('name' => 'TEST'))
            ->setTemplateKey('snippet1')
            ->setType(Snippet::TYPE_SNIPPET)
            ->setState(Structure::STATE_PUBLISHED)
            ->setParentUuid($snippetNode->getIdentifier());

        $this->mapper->saveRequest($request);

        $result = $this->manager->getAll('en');
    }
}
