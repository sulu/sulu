<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{
    public function contentAction($key)
    {
        $template = $this->getTemplateStructure($key);

        return $this->render(
            'SuluContentBundle:Template:content.html.twig',
            array('template' => $template)
        );
    }

    private function getTemplateStructure($key)
    {
        return $this->container->get('sulu.content.structure_manager')->getStructure($key);
    }

    public function listAction()
    {
        return $this->render('SuluContentBundle:Template:list.html.twig');
    }

    public function columnAction()
    {
        return $this->render('SuluContentBundle:Template:column.html.twig');
    }

}
