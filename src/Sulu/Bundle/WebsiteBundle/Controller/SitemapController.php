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
use Sulu\Component\Webspace\Portal;
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
        if (null !== ($response = $this->getDumpedIndexResponse($request))) {
            return $response;
        }

        $sitemap = $this->get('sulu_website.sitemap.xml_renderer')->renderIndex();
        if (!$sitemap) {
            $aliases = array_keys($this->get('sulu_website.sitemap.pool')->getProviders());

            return $this->sitemapPaginatedAction($request, reset($aliases), 1);
        }

        return $this->setCacheLifetime(new Response($sitemap));
    }

    /**
     * Returns index-response if dumped file exists.
     *
     * @param Request $request
     *
     * @return null|BinaryFileResponse
     */
    private function getDumpedIndexResponse(Request $request)
    {
        /** @var Portal $portal */
        $portal = $request->get('_sulu')->getAttribute('portal');
        $localization = $request->get('_sulu')->getAttribute('localization');
        if (!$localization) {
            $localization = $portal->getXDefaultLocalization();
        }

        $path = $this->get('sulu_website.sitemap.xml_dumper')->getIndexDumpPath(
            $request->getScheme(),
            $portal->getWebspace()->getKey(),
            $localization->getLocale(),
            $request->getHttpHost()
        );

        if (!$this->get('filesystem')->exists($path)) {
            return;
        }

        return $this->createBinaryFileResponse($path);
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
        if (null !== ($response = $this->getDumpedSitemapResponse($request, $alias, $page))) {
            return $response;
        }

        $portal = $request->get('_sulu')->getAttribute('portal');
        $localization = $request->get('_sulu')->getAttribute('localization');
        if (!$localization) {
            $localization = $portal->getXDefaultLocalization();
        }

        $sitemap = $this->get('sulu_website.sitemap.xml_renderer')->renderSitemap(
            $alias,
            $page,
            $localization->getLocale(),
            $portal,
            $request->getHttpHost(),
            $request->getScheme()
        );

        if (!$sitemap) {
            return new Response(null, 404);
        }

        return $this->setCacheLifetime(new Response($sitemap));
    }

    /**
     * Returns index-response if dumped file exists.
     *
     * @param Request $request
     * @param string $alias
     * @param int $page
     *
     * @return null|BinaryFileResponse
     */
    private function getDumpedSitemapResponse(Request $request, $alias, $page)
    {
        /** @var Portal $portal */
        $portal = $request->get('_sulu')->getAttribute('portal');
        $localization = $request->get('_sulu')->getAttribute('localization');
        if (!$localization) {
            $localization = $portal->getXDefaultLocalization();
        }

        $path = $this->get('sulu_website.sitemap.xml_dumper')->getDumpPath(
            $request->getScheme(),
            $portal->getWebspace()->getKey(),
            $localization->getLocale(),
            $request->getHttpHost(),
            $alias,
            $page
        );

        if (!$this->get('filesystem')->exists($path)) {
            return;
        }

        return $this->createBinaryFileResponse($path);
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
