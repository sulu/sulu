<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use InvalidArgumentException;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Basic class to render Website from phpcr content.
 */
abstract class WebsiteController extends Controller
{
    /**
     * Returns a rendered structure.
     *
     * @param StructureInterface $structure The structure, which has been loaded for rendering
     * @param array $attributes Additional attributes, which will be passed to twig
     * @param bool $preview Defines if the site is rendered in preview mode
     * @param bool $partial Defines if only the content block of the template should be rendered
     *
     * @return Response
     */
    protected function renderStructure(
        StructureInterface $structure,
        $attributes = [],
        $preview = false,
        $partial = false
    ) {
        // extract format twig file
        if (!$preview) {
            $request = $this->getRequest();
            $requestFormat = $request->getRequestFormat();
        } else {
            $requestFormat = 'html';
        }

        $viewTemplate = $structure->getView() . '.' . $requestFormat . '.twig';

        try {
            // get attributes to render template
            $data = $this->getAttributes($attributes, $structure, $preview);

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

            return new Response($content);
        } catch (InvalidArgumentException $e) {
            // template not found
            throw new HttpException(406, 'Error encountered when rendering content', $e);
        }
    }

    /**
     * Generates attributes.
     */
    protected function getAttributes($attributes, StructureInterface $structure = null, $preview = false)
    {
        return $this->get('sulu_website.resolver.parameter')->resolve(
            $attributes,
            $this->get('sulu_core.webspace.request_analyzer'),
            $structure,
            $preview
        );
    }

    /**
     * Returns rendered part of template specified by block.
     */
    protected function renderBlock($template, $block, $attributes = [])
    {
        $twig = $this->get('twig');
        $attributes = $twig->mergeGlobals($attributes);

        /** @var \Twig_Template $template */
        $template = $twig->loadTemplate($template);

        $level = ob_get_level();
        ob_start();
        try {
            $rendered = $template->renderBlock($block, $attributes);
            ob_end_clean();

            return $rendered;
        } catch (\Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }
}
