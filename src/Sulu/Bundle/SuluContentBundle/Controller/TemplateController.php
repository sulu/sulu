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
        $template = $this->getTemplate($key);

        return $this->render('SuluContentBundle:Template:content.html.twig', array('template' => $template));
    }

    private function getTemplate($key)
    {
        // TODO get Template
        // perhaps? $this->get('template.parser')->get($key);
        return array(
            'key' => 'overview',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifeTime' => 2400,
            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'textLine',
                    'mandatory' => true,
                    'multilingual' => true
                ),
                'url' => array(
                    'name' => 'url',
                    'type' => 'resourceLocator',
                    'mandatory' => true,
                    'multilingual' => true
                ),
                'article' => array(
                    'name' => 'article',
                    'type' => 'textArea',
                    'mandatory' => false,
                    'multilingual' => true
                )
            )
        );
    }

}
