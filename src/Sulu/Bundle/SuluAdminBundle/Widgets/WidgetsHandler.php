<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Widgets;

use Symfony\Component\Templating\EngineInterface;

/**
 * Class WidgetsHandler
 * @package Sulu\Bundle\AdminBundle\Widgets
 */
class WidgetsHandler implements WidgetsHandlerInterface
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
    protected $template = 'SuluAdminBundle:Widgets:widgets.html.twig';

    /**
     * @var string
     */
    protected $header;

    function __construct(EngineInterface $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    public function addWidget(WidgetInterface $widget, $alias)
    {
        $this->widgets[$alias] = array('instance' => $widget);
    }

    /**
     * renders widgets for given aliases
     * @param array $aliases
     * @param array $parameters
     * @return string
     */
    public function render($aliases, $parameters = array())
    {
        // process widgets
        $widgets = array();
        foreach ($aliases as $alias) {
            $widgets[] = array(
                'name' => $this->widgets[$alias]['instance']->getName(),
                'template' => $this->widgets[$alias]['instance']->getTemplate(),
                'data' => $this->widgets[$alias]['instance']->getData($parameters)
            );
        }

        // render template
        return $this->templateEngine->render($this->template, array(
            'widgets' => $widgets,
            'parameters' => $parameters
        ));
    }
}
