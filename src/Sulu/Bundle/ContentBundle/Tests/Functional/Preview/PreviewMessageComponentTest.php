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

use Sulu\Bundle\ContentBundle\Preview\PreviewMessageComponent;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * @group functional
 * @group preview
 */
class PreviewMessageComponentTest extends SuluTestCase
{
    /**
     * @var PreviewMessageComponent
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
        $this->component = $this->getContainer()->get('sulu_content.preview.message_component');
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

    public function renderCallback()
    {
        $args = func_get_args();
        /** @var StructureInterface $content */
        $content = $args[1]['content'];

        $result = $this->render(
            $content->getPropertyValue('title'),
            $content->getPropertyValue('article'),
            $content->getPropertyValue('block')
        );

        return $result;
    }

    public function indexCallback(StructureInterface $structure, $preview = false, $partial = false)
    {
        return new Response(
            $this->render(
                $structure->getPropertyValue('title'),
                $structure->getPropertyValue('article'),
                $structure->getPropertyValue('block'),
                $partial
            )
        );
    }

    public function render($title, $article, $block, $partial = false)
    {
        $template = '
            <div id="content" vocab="http://sulu.io/" typeof="Content">
                <h1 property="title">%s</h1>
                <h1 property="title">PREF: %s</h1>
                <div property="article">%s</div>
                <div property="block" typeof="collection">';
        $i = 0;
        foreach ($block as $b) {
            $subTemplate = '';
            foreach ($b['article'] as $a) {
                $subTemplate .= sprintf('<li property="article">%s</li>', $a);
            }
            $template .= sprintf(
                '<div rel="block" typeof="block"><h1 property="title">%s</h1><ul>%s</ul></div>',
                $b['title'],
                $subTemplate
            );
            $i++;
        }
        $template .= '</div></div>';
        if (!$partial) {
            $template = '<html vocab="http://schema.org/" typeof="Content"><body>' . $template . '</body></html>';
        }

        return sprintf($template, $title, $title, $article);
    }

    private function prepareClient(
        callable $sendCallback,
        $sendExpects = null,
        $name = 'CON_1',
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

        $client->resourceId = $name;

        return $client;
    }

    public function testStart()
    {
        $data = $this->prepareData();

        $i = -1;

        $clientForm = $this->prepareClient(
            function ($string) use (&$i) {
                $data = json_decode($string);
                $this->assertEquals($data->msg, 'OK');

                $i++;
                if ($i == 0) {
                    $this->assertEquals($data->other, false);
                } else {
                    $this->assertEquals($data->other, true);
                }
            },
            $this->exactly(2),
            'form'
        );
        $clientPreview = $this->prepareClient(
            function ($string) {
                $data = json_decode($string);
                $this->assertEquals($data->msg, 'OK');
                $this->assertEquals($data->other, true);
            },
            $this->once(),
            'preview'
        );

        $this->component->onMessage(
            $clientForm,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'languageCode' => 'de',
                    'webspaceKey' => 'sulu_io',
                    'type' => 'form',
                    'user' => '1'
                )
            )
        );

        $this->component->onMessage(
            $clientPreview,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'languageCode' => 'de',
                    'webspaceKey' => 'sulu_io',
                    'type' => 'preview',
                    'user' => '1'
                )
            )
        );
    }

    public function testUpdate()
    {
        $data = $this->prepareData();

        $i = 0;
        $clientForm1 = $this->prepareClient(
            function ($string) use (&$i) {
                $data = json_decode($string);

                if ($i == 0 && $data->command == 'start') {
                    $this->assertEquals($data->msg, 'OK');
                    $this->assertEquals($data->other, false);
                    $i++;
                } elseif ($i == 1 && $data->command == 'start') {
                    $this->assertEquals($data->msg, 'OK');
                    $this->assertEquals($data->other, true);
                } elseif (($i == 2 || $i == 3) && $data->command == 'update') {
                    $this->assertEquals($data->msg, 'OK');
                } else {
                    $this->assertTrue(false);
                }
            },
            $this->any(),
            'form1'
        );
        $clientPreview1 = $this->prepareClient(
            function ($string) use (&$i) {
                $data = json_decode($string);

                if ($i == 1 && $data->command == 'start') {
                    $this->assertEquals($data->msg, 'OK');
                    $this->assertEquals($data->other, true);
                    $i++;
                } elseif ($i == 2 && $data->command == 'changes') {
                    $this->assertEquals('asdf', $data->changes->title[0]);
                    $this->assertEquals('PREF: asdf', $data->changes->title[1]);
                    $i++;
                } elseif ($i == 3 && $data->command == 'changes') {
                    $this->assertEquals('qwertz', $data->changes->article[0]);
                } else {
                    $this->assertTrue(false);
                }
            },
            $this->any(),
            'preview1'
        );

        $clientForm2 = $this->prepareClient(
            function ($string) {
                $data = json_decode($string);

                if ($data->command != 'start') {
                    // no update will be sent
                    $this->assertTrue(false);
                }
            },
            $this->any(),
            'form2'
        );
        $this->component->onMessage(
            $clientForm2,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'languageCode' => 'de',
                    'webspaceKey' => 'sulu_io',
                    'type' => 'form',
                    'user' => '1'
                )
            )
        );

        $clientPreview2 = $this->prepareClient(
            function ($string) {
                $data = json_decode($string);

                if ($data->command != 'start') {
                    // no update will be sent
                    $this->assertTrue(false);
                }
            },
            $this->any(),
            'preview2'
        );
        $this->component->onMessage(
            $clientPreview2,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'sulu_io',
                    'type' => 'preview',
                    'user' => '1'
                )
            )
        );

        $this->component->onMessage(
            $clientForm1,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[1]->getUuid(),
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'sulu_io',
                    'type' => 'form',
                    'user' => '1'
                )
            )
        );

        $this->component->onMessage(
            $clientPreview1,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[1]->getUuid(),
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'sulu_io',
                    'type' => 'preview',
                    'user' => '1'
                )
            )
        );

        $this->component->onMessage(
            $clientForm1,
            json_encode(
                array(
                    'command' => 'update',
                    'content' => $data[1]->getUuid(),
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'sulu_io',
                    'type' => 'form',
                    'user' => '1',
                    'changes' => array(
                        'title' => 'asdf'
                    )

                )
            )
        );

        $this->component->onMessage(
            $clientForm1,
            json_encode(
                array(
                    'command' => 'update',
                    'content' => $data[1]->getUuid(),
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'sulu_io',
                    'type' => 'form',
                    'user' => '1',
                    'changes' => array(
                        'article' => 'qwertz'
                    )
                )
            )
        );
    }

    public function testReconnect()
    {
        $data = $this->prepareData();

        $i = -1;

        $clientForm = $this->prepareClient(
            function ($string) use (&$i) {
                $data = json_decode($string);
                $this->assertEquals($data->msg, 'OK');

                $i++;
                if ($i == 0) {
                    $this->assertEquals($data->other, false);
                } else {
                    $this->assertEquals($data->other, true);
                }
            },
            $this->exactly(2),
            'form'
        );
        $clientPreview = $this->prepareClient(
            function ($string) {
                $data = json_decode($string);
                $this->assertEquals($data->msg, 'OK');
                $this->assertEquals($data->other, true);
            },
            $this->once(),
            'preview'
        );

        $this->component->onMessage(
            $clientForm,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'languageCode' => 'de',
                    'webspaceKey' => 'sulu_io',
                    'type' => 'form',
                    'user' => '1'
                )
            )
        );

        $this->component->onMessage(
            $clientPreview,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'languageCode' => 'de',
                    'webspaceKey' => 'sulu_io',
                    'type' => 'preview',
                    'user' => '1'
                )
            )
        );
    }
}
