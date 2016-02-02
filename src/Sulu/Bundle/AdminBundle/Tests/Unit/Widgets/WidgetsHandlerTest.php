<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Widgets;

use Sulu\Bundle\AdminBundle\Widgets\WidgetInterface;
use Sulu\Bundle\AdminBundle\Widgets\WidgetsHandler;
use Sulu\Bundle\AdminBundle\Widgets\WidgetsHandlerInterface;
use Symfony\Component\Templating\EngineInterface;

class WidgetsHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Twig_LoaderInterface
     */
    private $templates;

    /**
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * @var WidgetsHandlerInterface
     */
    private $widgetsHandler;

    protected function setUp()
    {
        $this->templateEngine = $this->getMock('\Symfony\Component\Templating\EngineInterface');

        $this->widgetsHandler = new WidgetsHandler($this->templateEngine, [
            'test-group' => [
                'mappings' => [
                    'group-widget-1',
                    'group-widget-1',
                    'group-widget-3',
                    'group-widget-2',
                ],
            ],
        ]);
    }

    /**
     * @param $name
     * @param $template
     * @param $data
     *
     * @return WidgetInterface
     */
    private function getWidget($name, $template, $data)
    {
        $widget = $this->getMock('\Sulu\Bundle\AdminBundle\Widgets\WidgetInterface');

        $widget->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $widget->expects($this->any())
            ->method('getTemplate')
            ->will($this->returnValue($template));

        if (is_callable($data)) {
            $data = $this->returnCallback($data);
        } else {
            $data = $this->returnValue($data);
        }

        $widget->expects($this->any())
            ->method('getData')
            ->will($data);

        return $widget;
    }

    public function testRender()
    {
        $this->widgetsHandler->addWidget(
            $this->getWidget('widget1', 'SuluTestBundle:widget:widget1.html.twig', ['test' => '1']),
            'widget-1'
        );
        $this->widgetsHandler->addWidget(
            $this->getWidget('widget3', 'SuluTestBundle:widget:widget3.html.twig', ['test' => '3']),
            'widget-3'
        );
        $this->widgetsHandler->addWidget(
            $this->getWidget('widget2', 'SuluTestBundle:widget:widget2.html.twig', ['test' => '2']),
            'widget-2'
        );

        $param = false;
        $template = false;

        $this->templateEngine
            ->expects($this->any())
            ->method('render')
            ->will(
                $this->returnCallback(
                    function ($t, $p) use (&$template, &$param) {
                        $param = $p;
                        $template = $t;

                        return true;
                    }
                )
            );

        $this->assertTrue($this->widgetsHandler->render(
            ['widget-2', 'widget-1', 'widget-3'],
            ['testParam' => 'super']
        ));
        $this->assertTrue(false !== $param);
        $this->assertTrue(false !== $template);

        $this->assertEquals('SuluAdminBundle:Widgets:widgets.html.twig', $template);
        $this->assertEquals(
            [
                'widgets' => [
                    [
                        'name' => 'widget2',
                        'template' => 'SuluTestBundle:widget:widget2.html.twig',
                        'data' => [
                            'test' => 2,
                        ],
                    ],
                    [
                        'name' => 'widget1',
                        'template' => 'SuluTestBundle:widget:widget1.html.twig',
                        'data' => [
                            'test' => 1,
                        ],
                    ],
                    [
                        'name' => 'widget3',
                        'template' => 'SuluTestBundle:widget:widget3.html.twig',
                        'data' => [
                            'test' => 3,
                        ],
                    ],
                ],
                'parameters' => [
                    'testParam' => 'super',
                ],
            ],
            $param
        );
    }

    public function testRenderWidgetGroup()
    {
        $this->widgetsHandler->addWidget(
            $this->getWidget('group-widget-1', 'SuluTestBundle:widget:widget1.html.twig', ['test' => '1']),
            'group-widget-1'
        );
        $this->widgetsHandler->addWidget(
            $this->getWidget('group-widget-3', 'SuluTestBundle:widget:widget3.html.twig', ['test' => '3']),
            'group-widget-3'
        );
        $this->widgetsHandler->addWidget(
            $this->getWidget('group-widget-2', 'SuluTestBundle:widget:widget2.html.twig', ['test' => '2']),
            'group-widget-2'
        );

        $param = false;
        $template = false;

        $this->templateEngine
            ->expects($this->any())
            ->method('render')
            ->will(
                $this->returnCallback(
                    function ($t, $p) use (&$template, &$param) {
                        $param = $p;
                        $template = $t;

                        return true;
                    }
                )
            );

        $this->assertTrue(
            $this->widgetsHandler->renderWidgetGroup('test-group', ['testParam' => 'super'])
        );
        $this->assertTrue(false !== $param);
        $this->assertTrue(false !== $template);
        $this->assertEquals('SuluAdminBundle:Widgets:widgets.html.twig', $template);
        $this->assertEquals(
            [
                'widgets' => [
                    [
                        'name' => 'group-widget-1',
                        'template' => 'SuluTestBundle:widget:widget1.html.twig',
                        'data' => [
                            'test' => 1,
                        ],
                    ],
                    [
                        'name' => 'group-widget-1',
                        'template' => 'SuluTestBundle:widget:widget1.html.twig',
                        'data' => [
                            'test' => 1,
                        ],
                    ],
                    [
                        'name' => 'group-widget-3',
                        'template' => 'SuluTestBundle:widget:widget3.html.twig',
                        'data' => [
                            'test' => 3,
                        ],
                    ],
                    [
                        'name' => 'group-widget-2',
                        'template' => 'SuluTestBundle:widget:widget2.html.twig',
                        'data' => [
                            'test' => 2,
                        ],
                    ],
                ],
                'parameters' => [
                    'testParam' => 'super',
                ],
            ],
            $param
        );
    }
}
