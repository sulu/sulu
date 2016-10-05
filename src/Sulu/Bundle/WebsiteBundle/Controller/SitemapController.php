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

use Sulu\Component\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders a xml sitemap.
 */
class SitemapController extends WebsiteController
{
    /**
     * Render sitemap-index of all available sitemap.xml files.
     * If only one provider exists this provider will be rendered directly.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $dumpDir = $this->getDumpDir($request);
        if ($this->get('filesystem')->exists($dumpDir . '/sitemap.xml')) {
            return $this->createBinaryFileResponse($dumpDir . '/sitemap.xml');
        }

        $pool = $this->get('sulu_website.sitemap.pool');
        if (!$pool->needsIndex()) {
            return $this->sitemapPaginatedAction($request, $pool->getFirstAlias(), 1);
        }

        return $this->setCacheLifetime(
            $this->render('SuluWebsiteBundle:Sitemap:sitemap-index.xml.twig', ['sitemaps' => $pool->getIndex()])
        );
    }

    /**
     * Redirect to the first page of a single sitemap provider.
     *
     * @param string $alias
     *
     * @return Response
     */
    public function sitemapAction($alias)
    {
        if (!$this->get('sulu_website.sitemap.pool')->hasProvider($alias)) {
            return new Response(null, 404);
        }

        return $this->redirectToRoute('sulu_website.paginated_sitemap', ['alias' => $alias, 'page' => 1], 301);
    }

    /**
     * Render a single page for a single sitemap.xml provider.
     *
     * @param Request $request
     * @param string $alias
     * @param int $page
     *
     * @return Response
     */
    public function sitemapPaginatedAction(Request $request, $alias, $page)
    {
        $dumpDir = $this->getDumpDir($request);
        if ($this->get('filesystem')->exists($dumpDir . '/sitemaps/' . $alias . '-' . $page . '.xml')) {
            return $this->createBinaryFileResponse($dumpDir . '/sitemaps/' . $alias . '-' . $page . '.xml');
        }

        if (!$this->get('sulu_website.sitemap.pool')->hasProvider($alias)) {
            return new Response(null, 404);
        }

        $portal = $request->get('_sulu')->getAttribute('portal');
        $webspace = $request->get('_sulu')->getAttribute('webspace');
        $localization = $request->get('_sulu')->getAttribute('localization');

        if (!$localization) {
            $localization = $portal->getDefaultLocalization();
        }

        $provider = $this->get('sulu_website.sitemap.pool')->getProvider($alias);

        if ($provider->getMaxPage() < $page) {
            return new Response(null, 404);
        }

        $entries = $provider->build(
            $page,
            $portal->getKey(),
            $localization->getLocale()
        );

        return $this->setCacheLifetime(
            $this->render(
                'SuluWebsiteBundle:Sitemap:sitemap.xml.twig',
                [
                    'webspaceKey' => $webspace->getKey(),
                    'locale' => $localization->getLocale(),
                    'defaultLocale' => $portal->getXDefaultLocalization()->getLocale(),
                    'domain' => $request->getHttpHost(),
                    'scheme' => $request->getScheme(),
                    'entries' => $entries,
                ]
            )
        );
    }

    /**
     * Returns dump-dir.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getDumpDir(Request $request)
    {
        $dumpDir = $this->getParameter('sulu_website.sitemap.dump_dir');

        return sprintf('%s/%s/%s', $dumpDir, $request->getScheme(), $request->getHttpHost());
    }

    /**
     * Set cache headers.
     *
     * @param Response $response
     *
     * @return Response
     */
    private function setCacheLifetime(Response $response)
    {
        $response->headers->set(
            HttpCache::HEADER_REVERSE_PROXY_TTL,
            $response->getAge() + $this->container->getParameter('sulu_website.sitemap.cache.lifetime')
        );

        return $response->setMaxAge(240)
            ->setSharedMaxAge(960);
    }

    /**
     * Create a binary file response.
     *
     * @param string $file
     *
     * @return BinaryFileResponse
     */
    private function createBinaryFileResponse($file)
    {
        $response = new BinaryFileResponse($file);
        $response->headers->addCacheControlDirective('no-store', true);

        return $response;
    }
}
