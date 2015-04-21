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
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
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

            // remove empty first line
            if (ob_get_length()) {
                ob_clean();
            }

            return new Response($content);
        } catch (InvalidArgumentException $e) {
            // template not found
            throw new HttpException(406, 'Error encountered when rendering content', $e);
        }
    }

    /**
     * Generates attributes
     */
    protected function getAttributes($attributes, StructureInterface $structure = null, $preview = false)
    {
        // TODO call paramter resolver
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
