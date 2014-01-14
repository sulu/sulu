<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Component\Content\StructureInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

abstract class WebsiteController extends Controller
{
    protected function renderStructure(
        StructureInterface $structure,
        $attributes = array(),
        $preview = false,
        $partial = false
    )
    {
        // if partial render only content block else full page
        if ($partial) {
            $content = $this->renderBlock(
                $structure->getView(),
                'content',
                array_merge($attributes, array('content' => $structure))
            );
        } else {
            $content = parent::renderView(
                $structure->getView(),
                array_merge($attributes, array('content' => $structure))
            );
        }

        $response = new Response();
        $response->setContent($content);

        // if not preview enable cache handling
        if (!$preview) {
            $response->setPublic();
            $response->setPrivate();
            $response->setSharedMaxAge($structure->getCacheLifeTime());
            $response->setMaxAge($structure->getCacheLifeTime());
        }

        return $response;
    }

    protected function renderBlock($template, $block, $attributes = array())
    {
        $twig = $this->get('twig');
        $template = $twig->loadTemplate($template);
        return $template->renderBlock($block, $attributes);
    }
} 
