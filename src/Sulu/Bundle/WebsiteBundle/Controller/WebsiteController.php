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

use InvalidArgumentException;
use Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapper;
use Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Template\Exception\TemplateNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $request = $this->getRequest();
        $viewTemplate = str_replace('{_format}', $request->getRequestFormat(), $structure->getView());

        $structureData = $this->get('sulu.content.structure_resolver')->resolve($structure);

        $data = array_merge(
            $attributes,
            $structureData
        );

        try {
            // if partial render only content block else full page
            if ($partial) {
                $content = $this->renderBlock(
                    $viewTemplate,
                    'content',
                    $data
                );
            } else {
                $content = parent::renderView(
                    $viewTemplate,
                    $data
                );
            }

            $response = new Response();
            $response->setContent($content);

            // if not preview enable cache handling
            if (!$preview) {
                // mark the response as either public or private
                $response->setPublic();

                // set the private or shared max age
                $response->setMaxAge($structure->getCacheLifeTime());
                $response->setSharedMaxAge($structure->getCacheLifeTime());
            }

            return $response;
        } catch (InvalidArgumentException $ex) {
            // template not found
            return new Response(null, 406);
        }
    }

    /**
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
