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

use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapGeneratorInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders a xml sitemap
 * @package Sulu\Bundle\WebsiteBundle\Controller
 */
class SitemapController extends WebsiteController
{
    /**
     * Returns a rendered xmlsitemap
     * @return Response
     */
    public function indexAction()
    {
        /** @var RequestAnalyzerInterface $requestAnalyzer */
        $requestAnalyzer = $this->get('sulu_core.webspace.request_analyzer');
        /** @var SitemapGeneratorInterface $sitemapGenerator */
        $sitemapGenerator = $this->get('sulu_website.sitemap');
        $webspace = $requestAnalyzer->getCurrentWebspace();

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        return $this->render(
            'SuluWebsiteBundle:Sitemap:sitemap.xml.twig',
            array(
                'sitemap' => $sitemapGenerator->generateAllLocals($webspace->getKey(), true),
                'webspaceKey' => $webspace->getKey()
            ),
            $response
        );
    }
}
