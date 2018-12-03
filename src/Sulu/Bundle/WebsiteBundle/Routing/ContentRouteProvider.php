<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use PHPCR\RepositoryException;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * The PortalRouteProvider should load the dynamic routes created by Sulu.
 */
class ContentRouteProvider implements RouteProviderInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var ResourceLocatorStrategyPoolInterface
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @param DocumentManagerInterface $documentManager
     * @param DocumentInspector $documentInspector
     * @param ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool
     * @param StructureManagerInterface $structureManager
     * @param RequestAnalyzerInterface $requestAnalyzer
     */
    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool,
        StructureManagerInterface $structureManager,
        RequestAnalyzerInterface $requestAnalyzer
    ) {
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->resourceLocatorStrategyPool = $resourceLocatorStrategyPool;
        $this->structureManager = $structureManager;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $collection = new RouteCollection();

        if ('' === $request->getRequestFormat()) {
            return $collection;
        }

        /** @var RequestAttributes $attributes */
        $attributes = $request->attributes->get('_sulu');
        if (!$attributes) {
            return $collection;
        }

        $matchType = $attributes->getAttribute('matchType');

        // no portal information without localization supported
        if (null === $attributes->getAttribute('localization')
            && RequestAnalyzerInterface::MATCH_TYPE_PARTIAL !== $matchType
            && RequestAnalyzerInterface::MATCH_TYPE_REDIRECT !== $matchType
        ) {
            return $collection;
        }

        $resourceLocator = $this->decodePathInfo($attributes->getAttribute('resourceLocator'));
        $prefix = $attributes->getAttribute('resourceLocatorPrefix');

        $pathInfo = $this->decodePathInfo($request->getPathInfo());
        $htmlRedirect = $pathInfo !== $prefix . $resourceLocator
                        && in_array($request->getRequestFormat(), ['htm', 'html']);

        if ($htmlRedirect
            || RequestAnalyzerInterface::MATCH_TYPE_REDIRECT == $matchType
            || RequestAnalyzerInterface::MATCH_TYPE_PARTIAL == $matchType
        ) {
            return $collection;
        }

        // just show the page
        $portal = $attributes->getAttribute('portal');
        $locale = $attributes->getAttribute('localization')->getLocale();
        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey(
            $portal->getWebspace()->getKey()
        );

        try {
            // load content by url ignore ending trailing slash
            /** @var PageDocument $document */
            $document = $this->documentManager->find(
                $resourceLocatorStrategy->loadByResourceLocator(
                    rtrim($resourceLocator, '/'),
                    $portal->getWebspace()->getKey(),
                    $locale
                ),
                $locale,
                [
                    'load_ghost_content' => false,
                ]
            );

            if (!$document->getTitle()) {
                // If the title is empty the document does not exist in this locale
                // Necessary because of https://github.com/sulu/sulu/issues/2724, otherwise locale could be checked
                return $collection;
            }

            if (preg_match('/\/$/', $resourceLocator) && $prefix) {
                // redirect page to page without slash at the end
                $url = $prefix . rtrim($resourceLocator, '/');
                if ($request->getQueryString()) {
                    $url .= '?' . $request->getQueryString();
                }
                $collection->add('redirect_' . uniqid(), $this->getRedirectRoute($request, $url));
            } elseif (RedirectType::INTERNAL === $document->getRedirectType()) {
                // redirect internal link
                $redirectUrl = $prefix . $document->getRedirectTarget()->getResourceSegment();

                if ($request->getQueryString()) {
                    $redirectUrl .= '?' . $request->getQueryString();
                }

                $collection->add(
                    $document->getStructureType() . '_' . $document->getUuid(),
                    $this->getRedirectRoute($request, $redirectUrl)
                );
            } elseif (RedirectType::EXTERNAL === $document->getRedirectType()) {
                $collection->add(
                    $document->getStructureType() . '_' . $document->getUuid(),
                    $this->getRedirectRoute($request, $document->getRedirectExternal())
                );
            } elseif (!$this->checkResourceLocator($resourceLocator, $prefix)) {
                return $collection;
            } else {
                // convert the page to a StructureBridge because of BC
                $metadata = $this->documentInspector->getStructureMetadata($document);
                if (!$metadata) {
                    return $collection;
                }

                /** @var PageBridge $structure */
                $structure = $this->structureManager->wrapStructure(
                    $this->documentInspector->getMetadata($document)->getAlias(),
                    $metadata
                );
                $structure->setDocument($document);

                // show the page
                $collection->add(
                    $document->getStructureType() . '_' . $document->getUuid(),
                    $this->getStructureRoute($request, $structure)
                );
            }
        } catch (ResourceLocatorNotFoundException $exc) {
            // just do not add any routes to the collection
        } catch (ResourceLocatorMovedException $exc) {
            // old url resource was moved
            $collection->add(
                $exc->getNewResourceLocatorUuid() . '_' . uniqid(),
                $this->getRedirectRoute($request, $prefix . $exc->getNewResourceLocator())
            );
        } catch (RepositoryException $exc) {
            // just do not add any routes to the collection
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName($name, $parameters = [])
    {
        // TODO: Implement getRouteByName() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutesByNames($names, $parameters = [])
    {
        // TODO
        return [];
    }

    /**
     * Checks if the resource locator is valid.
     * A resource locator with a slash only is not allowed, the only exception is when it is a single language
     * website, where the browser automatically adds the slash.
     *
     * @param string $resourceLocator
     * @param string $resourceLocatorPrefix
     *
     * @return bool
     */
    private function checkResourceLocator($resourceLocator, $resourceLocatorPrefix)
    {
        return !('/' === $resourceLocator && $resourceLocatorPrefix);
    }

    /**
     * @param Request $request
     * @param string $url
     *
     * @return Route
     */
    protected function getRedirectRoute(Request $request, $url)
    {
        // redirect to linked page
        return new Route(
            $this->decodePathInfo($request->getPathInfo()),
            [
                '_controller' => 'SuluWebsiteBundle:Redirect:redirect',
                'url' => $url,
            ]
        );
    }

    /**
     * @param Request $request
     * @param PageBridge $content
     *
     * @return Route
     */
    protected function getStructureRoute(Request $request, PageBridge $content)
    {
        return new Route(
            $this->decodePathInfo($request->getPathInfo()),
            [
                '_controller' => $content->getController(),
                'structure' => $content,
                'partial' => 'true' === $request->get('partial', 'false'),
            ]
        );
    }

    /**
     * Server encodes the url and symfony does not encode it
     * Symfony decodes this data here https://github.com/symfony/symfony/blob/3.3/src/Symfony/Component/Routing/Matcher/UrlMatcher.php#L91.
     *
     * @param $pathInfo
     *
     * @return string
     */
    private function decodePathInfo($pathInfo)
    {
        if (null === $pathInfo || '' === $pathInfo) {
            return '';
        }

        return '/' . ltrim(rawurldecode($pathInfo), '/');
    }
}
