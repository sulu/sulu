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
 * represents a single widget in a splitview
 * @package Sulu\Bundle\AdminBundle\SplitView
 */
interface SplitViewWidgetInterface
{
    /**
     * return name of widget
     * @return string
     */
    public function getName();

    /**
     * returns template name of widget
     * @return string
     */
    public function getTemplate();

    /**
     * returns data to render template
     * @param mixed $id
     * @return array
     */
    public function getData($id);
} 
