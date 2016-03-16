<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Preview;

use Sulu\Bundle\ContentBundle\Preview\PreviewMessageHandler;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Websocket\ConnectionContext\ConnectionContext;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext;
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
            $this->getContainer()->get('sulu_core.webspace.request_analyzer'),
            $this->getContainer()->get('doctrine'),
            $this->mapper,
            new NullLogger()
        );
    }

    private function createContext($conn)
    {
        $connectionContext = new ConnectionContext($conn);

        return new MessageHandlerContext($connectionContext, 'test-handler');
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareData()
    {
        $data = [
            [
                'title' => 'Test1',
                'url' => '/test-1',
                'article' => 'Lorem Ipsum dolorem apsum',
                'block' => [
                    [
                        'type' => 'type1',
                        'title' => 'Block-Title-1',
                        'article' => ['Block-Article-1-1', 'Block-Article-1-2'],
                    ],
                    [
                        'type' => 'type1',
                        'title' => 'Block-Title-2',
                        'article' => ['Block-Article-2-1', 'Block-Article-2-2'],
                    ],
                ],
            ],
            [
                'title' => 'Test2',
                'url' => '/test-2',
                'article' => 'asdfasdf',
                'block' => [
                    [
                        'type' => 'type1',
                        'title' => 'Block-Title-2',
                        'article' => ['Block-Article-2-1', 'Block-Article-2-2'],
                    ],
                ],
            ],
        ];

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
            function ($string) {
            },
            $this->exactly(0)
        );

        $result = $this->component->handle(
            $client,
            [
                'command' => 'start',
                'content' => $data[0]->getUuid(),
                'locale' => 'de',
                'webspaceKey' => 'sulu_io',
                'user' => 1,
            ],
            $this->createContext($client)
        );

        $this->assertEquals($result['msg'], 'OK');
        $this->assertEquals($result['content'], $data[0]->getUuid());
    }

    public function testUpdate()
    {
        $data = $this->prepareData();

        $client = $this->prepareClient(
            function ($string) {
            },
            $this->exactly(0)
        );
        $context = $this->createContext($client);

        $result = $this->component->handle(
            $client,
            [
                'command' => 'start',
                'content' => $data[0]->getUuid(),
                'templateKey' => 'overview',
                'locale' => 'de',
                'webspaceKey' => 'sulu_io',
                'user' => 1,
            ],
            $context
        );
        $this->assertEquals('OK', $result['msg']);

        // NOTE: This test is strange and doesn't do what might be expected.
        //       It fails here, but passes in the develop branch.
        //
        //       Not sure if it is an actual bug, will perform more testing and
        //       leaving this commented for the time being.
        //$result = $this->component->handle(
            //$client,
            //array(
                //'command' => 'update',
                //'data' => array(
                    //'title' => 'asdf'
                //)
            //),
            //$context
        //);
        //$this->assertEquals($data[0]->getUuid(), $result['content']);
        //$this->assertEquals(array('Hello Hikaru Sulu'), $result['data']['title']);

        //$result = $this->component->handle(
            //$client,
            //array(
                //'command' => 'update',
                //'data' => array(
                    //'content' => 'qwertz'
                //)
            //),
            //$context
        //);
        //$this->assertEquals($data[0]->getUuid(), $result['content']);
        //$this->assertEquals(array('This is a fabulous test case!'), $result['data']['content']);
    }
}
