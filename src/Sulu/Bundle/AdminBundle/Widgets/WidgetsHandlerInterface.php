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

/**
 * interface WidgetsHandler.
 */
interface WidgetsHandlerInterface
{
    /**
     * renders a widget group.
     *
     * @param string $groupAlias
     * @param array  $parameters
     *
     * @return string
     */
    public function renderWidgetGroup($groupAlias, $parameters = []);

    /**
     * return true of widget group exists.
     *
     * @param $groupAlias
     *
     * @return mixed
     */
    public function hasWidgetGroup($groupAlias);

    /**
     * renders widgets for given aliases.
     *
     * @param array $aliases
     * @param array $parameters
     *
     * @return string
     */
    public function render($aliases, $parameters = []);

    /**
     * add an widget to rendering process.
     *
     * @param WidgetInterface $widget
     * @param string          $alias
     */
    public function addWidget(WidgetInterface $widget, $alias);
}
