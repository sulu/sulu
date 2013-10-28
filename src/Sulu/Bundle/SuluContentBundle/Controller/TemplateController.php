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

use Sulu\Bundle\ContentBundle\Mapper\ContentMapper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{
    public function contentAction($key)
    {
        $template = $this->getTemplateDefinition($key);
        $types = $this->getTypeDefinitions();

        return $this->render(
            'SuluContentBundle:Template:content.html.twig',
            array('template' => $template, 'types' => $types)
        );
    }

    private function getTypeDefinitions()
    {
        // TODO get Types
        // perhaps? $this->get('content.parser.types')->get();
        return ContentMapper::$types;
    }

    private function getTemplateDefinition($key)
    {
        // TODO get Template
        // perhaps? $this->get('content.parser.template')->get($key);
        return ContentMapper::$template;
    }

}
