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

/**
 * interface SplitView
 * @package Sulu\Bundle\AdminBundle\SplitView
 */
interface SplitViewInterface
{
    /**
     * render all widgets in the right order
     * @param mixed $id
     * @param array $parameters
     * @return string
     */
    public function render($id, $parameters = array());

    /**
     * add an widget to rendering process
     * @param SplitViewWidgetInterface $widget
     * @param integer $priority
     */
    public function addWidget(SplitViewWidgetInterface $widget, $priority);
}
