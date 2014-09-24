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
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\HttpCache\HttpCache;
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
    ) {
        // extract format twig file
        $request = $this->getRequest();
        $viewTemplate = $structure->getView() . '.' . $request->getRequestFormat() . '.twig';

        // get attributes to render template
        $data = $this->getAttributes($attributes, $structure, $preview);

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

            // remove empty first line
            if (ob_get_length()) {
                ob_clean();
            }

            $response = new Response();
            $response->setContent($content);

            // if not preview enable cache handling
            if (!$preview) {
                // mark the response as either public or private
                $response->setPublic();

                // set the private and shared max age
                $response->setMaxAge(240);
                $response->setSharedMaxAge(960);

                // set reverse-proxy TTL (Symfony HttpCache, Varnish, ...)
                $response->headers->set(
                    HttpCache::HEADER_REVERSE_PROXY_TTL,
                    $response->getAge() + intval($structure->getCacheLifeTime())
                );
            }

            return $response;
        } catch (InvalidArgumentException $ex) {
            // template not found
            throw new HttpException(406);
        }
    }

    /**
     * Generates attributes
     */
    protected function getAttributes($attributes, StructureInterface $structure = null, $preview = false)
    {
        if ($structure !== null) {
            $structureData = $this->get('sulu_website.resolver.structure')->resolve($structure);
        } else {
            $structureData = array();
        }

        if (!$preview) {
            $requestAnalyzerData = $this
                ->get('sulu_website.resolver.request_analyzer')
                ->resolve(
                    $this->get('sulu_core.webspace.request_analyzer')
                );
        } else {
            $requestAnalyzerData = $this
                ->get('sulu_website.resolver.request_analyzer')
                ->resolveForPreview($structure->getWebspaceKey(), $structure->getLanguageCode());
        }

        return array_merge(
            $attributes,
            $structureData,
            $requestAnalyzerData
        );
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
