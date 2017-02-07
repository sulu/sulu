<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Widgets;

use Symfony\Component\Templating\EngineInterface;

/**
 * Class WidgetsHandler.
 */
class WidgetsHandler implements WidgetsHandlerInterface
{
    /**
     * @var WidgetInterface[]
     */
    protected $widgets = [];

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

    /**
     * @var array
     */
    protected $widgetGroups;

    public function __construct(EngineInterface $templateEngine, $widgetGroups)
    {
        $this->templateEngine = $templateEngine;
        $this->widgetGroups = $widgetGroups;
    }

    public function addWidget(WidgetInterface $widget, $alias)
    {
        $this->widgets[$alias] = $widget;
    }

    /**
     * renders a widget group.
     *
     * @param string $groupAlias
     * @param array  $parameters
     *
     * @return string
     *
     * @throws WidgetGroupNotFoundException
     */
    public function renderWidgetGroup($groupAlias, $parameters = [])
    {
        if (array_key_exists($groupAlias, $this->widgetGroups)) {
            return $this->render($this->widgetGroups[$groupAlias]['mappings'], $parameters);
        } else {
            throw new WidgetGroupNotFoundException('Widget group not found', $groupAlias);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasWidgetGroup($groupAlias)
    {
        return array_key_exists($groupAlias, $this->widgetGroups) && count($this->widgetGroups[$groupAlias]) > 0;
    }

    /**
     * renders widgets for given aliases.
     *
     * @param array $aliases
     * @param array $parameters
     *
     * @return string
     */
    public function render($aliases, $parameters = [])
    {
        // process widgets
        $widgets = [];
        foreach ($aliases as $alias) {
            $widgets[] = [
                'name' => $this->widgets[$alias]->getName(),
                'template' => $this->widgets[$alias]->getTemplate(),
                'data' => $this->widgets[$alias]->getData($parameters),
            ];
        }

        if (count($widgets) > 0) {
            // render template
            return $this->templateEngine->render(
                $this->template,
                [
                    'widgets' => $widgets,
                    'parameters' => $parameters,
                ]
            );
        }

        return;
    }
}
