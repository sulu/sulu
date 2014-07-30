<?php
/*
  * This file is part of the Sulu CMS.
  *
  * (c) MASSIVE ART WebServices GmbH
  *
  * This source file is subject to the MIT license that is bundled
  * with this source code in the file LICENSE.
  */

namespace Sulu\Bundle\ContactBundle\Widgets;

use Sulu\Bundle\AdminBundle\Widgets\WidgetInterface;

/**
 * example widget for contact controller
 *
 * @package Sulu\Bundle\ContactBundle\Widgets
 */
class Toolbar implements WidgetInterface
{
    /**
     * return name of widget
     *
     * @return string
     */
    public function getName()
    {
        return 'toolbar';
    }

    /**
     * returns template name of widget
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'SuluContactBundle:Widgets:toolbar.html.twig';
    }

    /**
     * returns data to render template
     *
     * @param array $options
     * @return array
     */
    public function getData($options)
    {
        // TODO: fetch contact here - (options contains all request parameters)
        return $options;
    }
}
