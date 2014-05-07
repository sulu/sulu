<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\SplitView;

use Symfony\Component\Templating\EngineInterface;

/**
 * Class SplitView
 * @package Sulu\Bundle\AdminBundle\SplitView
 */
class SplitView implements SplitViewInterface
{
    /**
     * @var array
     */
    protected $widgets = array();

    /**
     * @var EngineInterface
     */
    protected $templateEngine;

    /**
     * @var string
     */
    protected $template = 'SuluAdminBundle:SplitView:widgets.html.twig';

    /**
     * @var string
     */
    protected $header;

    function __construct(EngineInterface $templateEngine, $header, $template = null)
    {
        $this->templateEngine = $templateEngine;
        $this->header = $header;

        if ($template !== null) {
            $this->template = $template;
        }
    }

    public function addWidget(SplitViewWidgetInterface $widget, $priority)
    {
        $this->widgets[] = array('instance' => $widget, 'priority' => $priority);
    }

    /**
     * render all widgets in the right order
     * @param mixed $id
     * @param array $parameters
     * @return string
     */
    public function render($id, $parameters = array())
    {
        // sort widgets
        usort(
            $this->widgets,
            function ($widgetA, $widgetB) {
                if ($widgetA['priority'] === $widgetB['priority']) {
                    return 0;
                }

                return ($widgetA['priority'] > $widgetB['priority']) ? -1 : 1;
            }
        );

        // process widgets
        $widgets = array();
        foreach ($this->widgets as $widget) {
            $widgets[] = array(
                'priority' => $widget['priority'],
                'name' => $widget['instance']->getName(),
                'template' => $widget['instance']->getTemplate(),
                'data' => $widget['instance']->getData($id)
            );
        }

        // merge parameters
        $parameters = array_merge(
            array(
                'header' => $this->header,
                'widgets' => $widgets,
                'id' => $id
            ),
            $parameters
        );

        // render template
        return $this->templateEngine->render($this->template, $parameters);
    }
}
