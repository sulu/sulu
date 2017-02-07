<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Controller;

use Sulu\Component\Rest\RestController;

/**
 * Class TemplateController.
 */
class TemplateController extends RestController
{
    /**
     * Returns the template for the form of a filter.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterFormAction()
    {
        $conjunctions = $this->container->getParameter('sulu_resource.filters.conjunction');

        return $this->render(
            'SuluResourceBundle:Template:filter.form.html.twig',
            ['conjunctions' => $conjunctions]
        );
    }

    /**
     * Returns the template for the list of a filter.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterListAction()
    {
        return $this->render(
            'SuluResourceBundle:Template:filter.list.html.twig'
        );
    }
}
