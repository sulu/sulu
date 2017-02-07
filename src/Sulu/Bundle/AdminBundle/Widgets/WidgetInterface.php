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
 * represents a single widget.
 */
interface WidgetInterface
{
    /**
     * return name of widget.
     *
     * @return string
     */
    public function getName();

    /**
     * returns template name of widget.
     *
     * @return string
     */
    public function getTemplate();

    /**
     * returns data to render template.
     *
     * @param array $options
     *
     * @throws WidgetException
     *
     * @return array
     */
    public function getData($options);
}
