<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\SplitView;

use Sulu\Bundle\AdminBundle\SplitView\SplitView;
use Sulu\Bundle\AdminBundle\SplitView\SplitViewInterface;
use Sulu\Bundle\AdminBundle\SplitView\SplitViewWidgetInterface;
use Symfony\Component\Templating\EngineInterface;

class SplitViewTest extends \PHPUnit_Framework_TestCase
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
     * @var SplitViewInterface
     */
    private $splitView;

    protected function setUp()
    {
        $this->templateEngine = $this->getMock('\Symfony\Component\Templating\EngineInterface');

        $this->splitView = new SplitView($this->templateEngine, 'TEST-123');
    }

    /**
     * @param $name
     * @param $template
     * @param $data
     * @return SplitViewWidgetInterface
     */
    private function getWidget($name, $template, $data)
    {
        $widget = $this->getMock('\Sulu\Bundle\AdminBundle\SplitView\SplitViewWidgetInterface');

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
        $this->splitView->addWidget(
            $this->getWidget('widget1', 'SuluTestBundle:widget:widget1.html.twig', array('test' => '1')),
            10
        );
        $this->splitView->addWidget(
            $this->getWidget('widget3', 'SuluTestBundle:widget:widget3.html.twig', array('test' => '3')),
            5
        );
        $this->splitView->addWidget(
            $this->getWidget('widget2', 'SuluTestBundle:widget:widget2.html.twig', array('test' => '2')),
            20
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

        $this->assertTrue($this->splitView->render(1));
        $this->assertNotFalse($param);
        $this->assertNotFalse($template);

        $this->assertEquals('SuluAdminBundle:SplitView:widgets.html.twig', $template);
        $this->assertEquals(
            array(
                'header'=>'TEST-123',
                'id' => 1,
                'widgets' => array(
                    array(
                        'priority' => 20,
                        'name' => 'widget2',
                        'template' =>'SuluTestBundle:widget:widget2.html.twig',
                        'data' => array(
                            'test' => 2
                        )
                    ),
                    array(
                        'priority' => 10,
                        'name' => 'widget1',
                        'template' =>'SuluTestBundle:widget:widget1.html.twig',
                        'data' => array(
                            'test' => 1
                        )
                    ),
                    array(
                        'priority' => 5,
                        'name' => 'widget3',
                        'template' =>'SuluTestBundle:widget:widget3.html.twig',
                        'data' => array(
                            'test' => 3
                        )
                    )
                )
            ),
            $param
        );
    }
}
