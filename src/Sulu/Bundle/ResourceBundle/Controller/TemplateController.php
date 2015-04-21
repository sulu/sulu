<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Controller;

use Sulu\Component\Rest\RestController;

/**
 * Class TemplateController
 * @package Sulu\Bundle\ResourceBundle\Controller
 */
class TemplateController extends RestController
{
    /**
     * Returns the template for the form of a filter
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterFormAction()
    {
        return $this->render('SuluResourceBundle:Template:filter.form.html.twig');
    }

    /**
     * Returns the template for the list of a filter
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterListAction()
    {
        return $this->render('SuluResourceBundle:Template:filter.list.html.twig');
    }
}
