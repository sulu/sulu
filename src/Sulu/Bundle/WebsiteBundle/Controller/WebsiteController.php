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

use Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapper;
use Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Basic class to render Website from phpcr content
 * @package Sulu\Bundle\WebsiteBundle\Controller
 */
abstract class WebsiteController extends Controller
{
    /**
     * Returns a rendered structure
     */
    protected function renderStructure(
        StructureInterface $structure,
        $attributes = array(),
        $preview = false,
        $partial = false
    )
    {
        $contentDataResolver = $this->get('sulu.content.structure_content_resolver');
        $viewDataResolver = $this->get('sulu.content.structure_view_resolver');

        $data = array_merge($attributes, array(
            'content' => $contentDataResolver->resolve($structure),
            'view' => $viewDataResolver->resolve($structure)
        ));

        // if partial render only content block else full page
        if ($partial) {
            $content = $this->renderBlock(
                $structure->getView(),
                'content',
                $data
            );
        } else {
            $content = parent::renderView(
                $structure->getView(),
                $data
            );
        }

        $response = new Response();
        $response->setContent($content);

        // if not preview enable cache handling
        if (!$preview) {
            // mark the response as either public or private
            $response->setPublic();
            //$response->setPrivate();

            // set the private or shared max age
            //$response->setMaxAge($structure->getCacheLifeTime());
            $response->setSharedMaxAge($structure->getCacheLifeTime());
        }

        return $response;
    }

    /**
     * Returns rendered error response
     */
    protected function renderError($template, $parameters, $code = 404)
    {
        $content = $this->renderView(
            $template,
            $parameters
        );

        $response = new Response();
        $response->setStatusCode($code);

        $response->setContent($content);

        return $response;
    }

    /**
     * Returns rendered part of template specified by block
     */
    protected function renderBlock($template, $block, $attributes = array())
    {
        $twig = $this->get('twig');
        $template = $twig->loadTemplate($template);

        return $template->renderBlock($block, $attributes);
    }
} 
