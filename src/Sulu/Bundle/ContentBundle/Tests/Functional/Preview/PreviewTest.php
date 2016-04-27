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

use Sulu\Bundle\ContentBundle\Preview\PreviewCacheProviderInterface;
use Sulu\Bundle\ContentBundle\Preview\PreviewInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @group preview
 */
class PreviewTest extends SuluTestCase
{
    /**
     * @var PreviewInterface
     */
    private $preview;

    /**
     * @var PreviewCacheProviderInterface
     */
    private $previewCache;

    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * @var RequestStack
     */
    private $requestStack;

    protected function setUp()
    {
        parent::initPhpcr();

        $request = new Request();

        $this->requestStack = $this->getContainer()->get('request_stack');
        $this->requestStack->push($request);
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->preview = $this->getContainer()->get('sulu_content.preview');

        $webspace = $this->getContainer()->get('sulu_core.webspace.webspace_manager')->findWebspaceByKey('sulu_io');
        $localization = $webspace->getLocalization('de_at');

        $request = new Request(
            [],
            [],
            [
                '_sulu' => new RequestAttributes(
                    [
                        'webspaceKey' => $webspace->getKey(),
                        'webspace' => $webspace,
                        'locale' => $localization->getLocalization(),
                        'localization' => $localization,
                    ]
                ),
            ]
        );

        $this->requestStack->push($request);

        $class = new \ReflectionClass(get_class($this->preview));
        $property = $class->getProperty('previewCache');
        $property->setAccessible(true);

        $this->previewCache = $property->getValue($this->preview);
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

        $data[0] = $this->mapper->save($data[0], 'overview', 'sulu_io', 'de_at', 1);
        $data[1] = $this->mapper->save($data[1], 'overview', 'sulu_io', 'de_at', 1);

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
        $template = "<div id=\"content\" vocab=\"http://sulu.io/\" typeof=\"Content\">\n" .
            "<h1 property=\"title\">%s</h1>\n" .
            "<h1 property=\"title\">PREF: %s</h1>\n" .
            "<div property=\"article\">%s</div>\n" .
            "<div property=\"block\" typeof=\"collection\">\n";

        $i = 0;
        foreach ($block as $b) {
            $subTemplate = '';
            foreach ($b['article'] as $a) {
                $subTemplate .= sprintf("<li property=\"article\">%s</li>\n", $a);
            }
            $template .= sprintf(
                "<div rel=\"block\" typeof=\"block\">\n<h1 property=\"title\">%s</h1>\n<ul>\n%s</ul>\n</div>\n",
                $b['title'],
                $subTemplate
            );
            ++$i;
        }
        $template .= "</div>\n</div>\n";
        if (!$partial) {
            $template = "<html vocab=\"http://schema.org/\" typeof=\"Content\">\n<body>\n" . $template . "</body>\n</html>\n";
        }

        return sprintf($template, $title, $title, $article);
    }

    public function testStartPreview()
    {
        $data = $this->prepareData();

        $content = $this->preview->start(1, $data[0]->getUuid(), 'sulu_io', 'de_at');

        // check result
        $this->assertEquals('Test1', $content->getPropertyValue('title'));
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->getPropertyValue('article'));

        // check cache
        $cachedPage = $this->previewCache->fetchStructure(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $this->assertNotNull($cachedPage);
        $this->assertEquals('Test1', $cachedPage->getPropertyValue('title'));
        $this->assertEquals('Lorem Ipsum dolorem apsum', $cachedPage->getPropertyValue('article'));
    }

    public function testStopPreview()
    {
        $data = $this->prepareData();

        $this->preview->start(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $this->assertTrue($this->previewCache->contains(1, $data[0]->getUuid(), 'sulu_io', 'de_at'));

        $this->preview->stop(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $this->assertFalse($this->previewCache->contains(1, $data[0]->getUuid(), 'sulu_io', 'de_at'));
    }

    public function testUpdate()
    {
        $data = $this->prepareData();

        $this->preview->start(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $this->preview->updateProperty(1, $data[0]->getUuid(), 'sulu_io', 'de_at', 'title', 'aaaa');
        $content = $this->preview->getChanges(1, $data[0]->getUuid(), 'sulu_io', 'de_at');

        // check result
        $this->assertEquals(
            [['property' => 'title', 'html' => 'aaaa'], ['property' => 'title', 'html' => 'PREF: aaaa']],
            $content['title']
        );

        // check cache
        $this->assertTrue($this->previewCache->contains(1, $data[0]->getUuid(), 'sulu_io', 'de_at'));
        $content = $this->previewCache->fetchStructure(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $this->assertEquals('aaaa', $content->getPropertyValue('title'));
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->getPropertyValue('article'));
    }

    public function testUpdateSequence()
    {
        $data = $this->prepareData();

        $this->preview->start(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $this->preview->updateProperty(
            1,
            $data[0]->getUuid(),
            'sulu_io',
            'de_at',
            'block,0,article,0',
            'New-Block-Article-1-1'
        );
        $this->preview->updateProperty(
            1,
            $data[0]->getUuid(),
            'sulu_io',
            'de_at',
            'block,0,article,1',
            'New-Block-Article-1-2'
        );
        $this->preview->updateProperty(
            1,
            $data[0]->getUuid(),
            'sulu_io',
            'de_at',
            'block,0,title',
            'New-Block-Title-1'
        );
        $this->preview->updateProperty(
            1,
            $data[0]->getUuid(),
            'sulu_io',
            'de_at',
            'block,1,title',
            'New-Block-Title-2'
        );
        $changes = $this->preview->getChanges(
            1,
            $data[0]->getUuid(),
            'sulu_io',
            'de_at'
        );

        // check result
        $this->assertEquals(1, count($changes['block,0']));
        $this->assertEquals(
            [
                'rel' => 'block',
                'typeof' => 'block',
                'html' => "\n<h1 property=\"title\">New-Block-Title-1</h1>\n" .
                    "<ul>\n" .
                    "<li property=\"article\">New-Block-Article-1-1</li>\n" .
                    "<li property=\"article\">New-Block-Article-1-2</li>\n" .
                    '</ul>',
            ],
            $changes['block,0'][0]
        );
        $this->assertEquals(1, count($changes['block,1']));
        $this->assertEquals(
            [
                'rel' => 'block',
                'typeof' => 'block',
                'html' => "\n<h1 property=\"title\">New-Block-Title-2</h1>\n" .
                    "<ul>\n" .
                    "<li property=\"article\">Block-Article-2-1</li>\n" .
                    "<li property=\"article\">Block-Article-2-2</li>\n" .
                    '</ul>',
            ],
            $changes['block,1'][0]
        );

        // check cache
        $this->assertTrue($this->previewCache->contains(1, $data[0]->getUuid(), 'sulu_io', 'de_at'));
        $content = $this->previewCache->fetchStructure(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $this->assertEquals(
            [
                [
                    'type' => 'type1',
                    'title' => 'New-Block-Title-1',
                    'article' => [
                        'New-Block-Article-1-1',
                        'New-Block-Article-1-2',
                    ],
                ],
                [
                    'type' => 'type1',
                    'title' => 'New-Block-Title-2',
                    'article' => [
                        'Block-Article-2-1',
                        'Block-Article-2-2',
                    ],
                ],
            ],
            $content->getPropertyValue('block')
        );
    }

    public function testRender()
    {
        $data = $this->prepareData();

        $this->preview->start(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $response = $this->preview->render(
            1,
            $data[0]->getUuid(),
            'sulu_io',
            'de_at'
        );

        $expected = $this->render(
            'Test1',
            'Lorem Ipsum dolorem apsum',
            [
                [
                    'title' => 'Block-Title-1',
                    'article' => [
                        'Block-Article-1-1',
                        'Block-Article-1-2',
                    ],
                ],
                [
                    'title' => 'Block-Title-2',
                    'article' => [
                        'Block-Article-2-1',
                        'Block-Article-2-2',
                    ],
                ],
            ]
        );
        $this->assertEquals($expected, $response);
    }

    public function testRealScenario()
    {
        $data = $this->prepareData();

        // start preview from FORM
        $content = $this->preview->start(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $this->assertEquals('Test1', $content->getPropertyValue('title'));
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->getPropertyValue('article'));

        // render PREVIEW
        $response = $this->preview->render(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $expected = $this->render(
            'Test1',
            'Lorem Ipsum dolorem apsum',
            [
                [
                    'title' => 'Block-Title-1',
                    'article' => [
                        'Block-Article-1-1',
                        'Block-Article-1-2',
                    ],
                ],
                [
                    'title' => 'Block-Title-2',
                    'article' => [
                        'Block-Article-2-1',
                        'Block-Article-2-2',
                    ],
                ],
            ]
        );
        $this->assertEquals($expected, $response);

        // change a property in FORM
        $content = $this->preview->updateProperty(1, $data[0]->getUuid(), 'sulu_io', 'de_at', 'title', 'New Title');
        $this->assertEquals('New Title', $content->getPropertyValue('title'));
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->getPropertyValue('article'));

        $content = $this->preview->updateProperty(1, $data[0]->getUuid(), 'sulu_io', 'de_at', 'article', 'asdf');
        $this->assertEquals('New Title', $content->getPropertyValue('title'));
        $this->assertEquals('asdf', $content->getPropertyValue('article'));

        // update PREVIEW
        $changes = $this->preview->getChanges(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $this->assertEquals(2, count($changes));
        $this->assertEquals(
            [['property' => 'title', 'html' => 'New Title'], ['property' => 'title', 'html' => 'PREF: New Title']],
            $changes['title']
        );
        $this->assertEquals([['property' => 'article', 'html' => 'asdf']], $changes['article']);

        // update PREVIEW
        $changes = $this->preview->getChanges(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $this->assertEquals(0, count($changes));

        // rerender PREVIEW
        $response = $this->preview->render(1, $data[0]->getUuid(), 'sulu_io', 'de_at');
        $expected = $this->render(
            'New Title',
            'asdf',
            [
                [
                    'title' => 'Block-Title-1',
                    'article' => [
                        'Block-Article-1-1',
                        'Block-Article-1-2',
                    ],
                ],
                [
                    'title' => 'Block-Title-2',
                    'article' => [
                        'Block-Article-2-1',
                        'Block-Article-2-2',
                    ],
                ],
            ]
        );
        $this->assertEquals($expected, $response);
    }
}
