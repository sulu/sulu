<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Preview;

use Sulu\Bundle\ContentBundle\Preview\PreviewMessageHandler;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Log\NullLogger;

/**
 * @group functional
 * @group preview
 */
class PreviewMessageHandlerTest extends SuluTestCase
{
    /**
     * @var PreviewMessageHandler
     */
    private $component;

    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    protected function setUp()
    {
        parent::initPhpcr();

        $this->mapper = $this->getContainer()->get('sulu.content.mapper');

        $this->component = new PreviewMessageHandler(
            $this->getContainer()->get('sulu_content.preview'),
            $this->getContainer()->get('sulu_core.webspace.request_analyzer.admin'),
            $this->getContainer()->get('doctrine'),
            new NullLogger()
        );
    }

    private function createContext($conn)
    {
        $context = $this->getMockBuilder('Sulu\Bundle\ContentBundle\Preview\PreviewConnectionContext')
            ->setConstructorArgs(array($conn))
            ->setMethods(array('getAdminUser'))
            ->getMock();

        $user = $this->getMockBuilder('Sulu\Component\Security\UserInterface')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getId',
                    'getLocale',
                    'getRoles',
                    'getPassword',
                    'getSalt',
                    'getUsername',
                    'eraseCredentials'
                )
            )
            ->getMock();

        $user->expects($this->any())->method('getId')->willReturn(1);

        $context->expects($this->any())->method('getAdminUser')->willReturn($user);

        return $context;
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareData()
    {
        $data = array(
            array(
                'title' => 'Test1',
                'url' => '/test-1',
                'article' => 'Lorem Ipsum dolorem apsum',
                'block' => array(
                    array(
                        'type' => 'type1',
                        'title' => 'Block-Title-1',
                        'article' => array('Block-Article-1-1', 'Block-Article-1-2')
                    ),
                    array(
                        'type' => 'type1',
                        'title' => 'Block-Title-2',
                        'article' => array('Block-Article-2-1', 'Block-Article-2-2')
                    )
                )
            ),
            array(
                'title' => 'Test2',
                'url' => '/test-2',
                'article' => 'asdfasdf',
                'block' => array(
                    array(
                        'type' => 'type1',
                        'title' => 'Block-Title-2',
                        'article' => array('Block-Article-2-1', 'Block-Article-2-2')
                    )
                )
            )
        );

        $data[0] = $this->mapper->save($data[0], 'overview', 'sulu_io', 'en', 1);
        $data[1] = $this->mapper->save($data[1], 'overview', 'sulu_io', 'en', 1);

        return $data;
    }

    private function prepareClient(
        callable $sendCallback,
        $sendExpects = null,
        callable $closeCallback = null,
        $closeExpects = null
    ) {
        if ($sendExpects == null) {
            $sendExpects = $this->any();
        }
        if ($closeExpects == null) {
            $closeExpects = $this->any();
        }
        $client = $this->getMock('Ratchet\ConnectionInterface');

        $client
            ->expects($sendExpects)
            ->method('send')
            ->will($this->returnCallback($sendCallback));

        if ($closeCallback != null) {
            $client
                ->expects($closeExpects)
                ->method('close')
                ->will($this->returnCallback($closeCallback));
        }

        $client->resourceId = uniqid();

        return $client;
    }

    public function testStart()
    {
        $data = $this->prepareData();

        $client = $this->prepareClient(
            function ($string) use ($data) {
                $decoded = json_decode($string);
                $this->assertEquals($decoded->msg, 'OK');
                $this->assertEquals($decoded->content, $data[0]->getUuid());
            },
            $this->exactly(1)
        );

        $this->component->handle(
            $client,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'locale' => 'de',
                    'webspaceKey' => 'sulu_io',
                )
            ),
            $this->createContext($client)
        );
    }

    public function testUpdate()
    {
        $data = $this->prepareData();

        $i = 0;
        $client = $this->prepareClient(
            function ($string) use (&$i, $data) {
                $decoded = json_decode($string, true);

                if ($i == 0 && $decoded['command'] == 'start') {
                    $this->assertEquals('OK', $decoded['msg']);
                    $i++;
                } elseif ($i == 1 && $decoded['command'] == 'update') {
                    $this->assertEquals($data[0]->getUuid(), $decoded['content']);
                    $this->assertEquals(array('Hello Hikaru Sulu'), $decoded['data']['title']);
                    $i++;
                } elseif ($i == 2 && $decoded['command'] == 'update') {
                    $this->assertEquals($data[0]->getUuid(), $decoded['content']);
                    $this->assertEquals(array('This is a fabulous test case!'), $decoded['data']['content']);
                    $i++;
                } else {
                    $this->assertTrue(false);
                }
            },
            $this->exactly(3)
        );

        $this->component->handle(
            $client,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'templateKey' => 'overview',
                    'locale' => 'de',
                    'webspaceKey' => 'sulu_io'
                )
            ),
            $this->createContext($client)
        );

        $this->component->handle(
            $client,
            json_encode(
                array(
                    'command' => 'update',
                    'data' => array(
                        'title' => 'asdf'
                    )

                )
            ),
            $this->createContext($client)
        );

        $this->component->handle(
            $client,
            json_encode(
                array(
                    'command' => 'update',
                    'data' => array(
                        'content' => 'qwertz'
                    )
                )
            ),
            $this->createContext($client)
        );
    }
}
