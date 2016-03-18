<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Attributes;

use PHPCR\RepositoryException;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts attributes from request for the sulu-website.
 */
class WebsiteRequestProcessor implements RequestProcessorInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        ContentMapperInterface $contentMapper,
        $environment
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->contentMapper = $contentMapper;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $url = $request->getHost() . $request->getPathInfo();
        $portalInformations = $this->webspaceManager->findPortalInformationsByUrl(
            $url,
            $this->environment
        );

        if (count($portalInformations) === 0) {
            return new RequestAttributes();
        } elseif (count($portalInformations) === 1) {
            return $this->processPortalInformation($request, reset($portalInformations));
        }

        usort(
            $portalInformations,
            function (PortalInformation $a, PortalInformation $b) {
                return $a->getType() > $b->getType();
            }
        );

        $redirectTypes = [RequestAnalyzerInterface::MATCH_TYPE_FULL, RequestAnalyzerInterface::MATCH_TYPE_PARTIAL];

        foreach ($portalInformations as $portalInformation) {
            // take first none full match
            if (!in_array($portalInformation->getType(), $redirectTypes)) {
                return $this->processPortalInformation($request, $portalInformation);
            }

            // if there exists a resource-locator take this portal
            if (null !== $content = $this->checkForResourceLocator($request, $portalInformation)) {
                return $this->processPortalInformation($request, $portalInformation, $content);
            }
        }

        return new RequestAttributes();
    }

    /**
     * Returns the request attributes for given portal information.
     *
     * @param Request $request
     * @param PortalInformation $portalInformation
     * @param mixed $content
     *
     * @return RequestAttributes
     */
    protected function processPortalInformation(
        Request $request,
        PortalInformation $portalInformation,
        $content = null
    ) {
        $attributes = ['requestUri' => $request->getUri(), 'content' => $content];

        if ($portalInformation === null) {
            return new RequestAttributes($attributes);
        }

        if (null !== $localization = $portalInformation->getLocalization()) {
            $request->setLocale($portalInformation->getLocalization()->getLocalization());
        }

        $attributes['portalInformation'] = $portalInformation;

        $attributes['getParameter'] = $request->query->all();
        $attributes['postParameter'] = $request->request->all();

        $attributes['matchType'] = $portalInformation->getType();
        $attributes['redirect'] = $portalInformation->getRedirect();
        $attributes['analyticsKey'] = $portalInformation->getAnalyticsKey();

        $attributes['portalUrl'] = $portalInformation->getUrl();
        $attributes['webspace'] = $portalInformation->getWebspace();

        if ($portalInformation->getType() === RequestAnalyzerInterface::MATCH_TYPE_REDIRECT) {
            return new RequestAttributes($attributes);
        }

        $attributes['localization'] = $portalInformation->getLocalization();
        $attributes['portal'] = $portalInformation->getPortal();
        $attributes['segment'] = $portalInformation->getSegment();

        list($resourceLocator, $format) = $this->getResourceLocatorFromRequest(
            $portalInformation,
            $request
        );

        $attributes['resourceLocator'] = $resourceLocator;
        $attributes['format'] = $format;
        $attributes['resourceLocatorPrefix'] = substr($portalInformation->getUrl(), strlen($request->getHost()));

        if (null !== $format) {
            $request->setRequestFormat($format);
        }

        return new RequestAttributes($attributes);
    }

    /**
     * Returns the content if a route exists.
     *
     * @param Request $request
     * @param PortalInformation $portalInformation
     *
     * @return StructureInterface|void
     */
    protected function checkForResourceLocator(Request $request, PortalInformation $portalInformation)
    {
        list($resourceLocator, $format) = $this->getResourceLocatorFromRequest(
            $portalInformation,
            $request
        );

        try {
            $content = $this->contentMapper->loadByResourceLocator(
                rtrim($resourceLocator, '/'),
                $portalInformation->getWebspaceKey(),
                $portalInformation->getLocale()
            );
        } catch (ResourceLocatorNotFoundException $ex) {
            return;
        } catch (ResourceLocatorMovedException $ex) {
            return $ex;
        } catch (RepositoryException $ex) {
            return;
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(RequestAttributes $attributes)
    {
        if (null === $attributes->getAttribute('portalInformation')) {
            throw new UrlMatchNotFoundException($attributes->getAttribute('requestUri'));
        }

        return true;
    }

    /**
     * Returns resource locator and format of current request.
     *
     * @param PortalInformation $portalInformation
     * @param Request $request
     *
     * @return array
     */
    private function getResourceLocatorFromRequest(PortalInformation $portalInformation, Request $request)
    {
        $path = $request->getPathInfo();

        // extract file and extension info
        $pathParts = explode('/', $path);
        $fileInfo = explode('.', array_pop($pathParts));

        $path = rtrim(implode('/', $pathParts), '/') . '/' . $fileInfo[0];
        $formatResult = null;
        if (count($fileInfo) > 1) {
            $formatResult = end($fileInfo);
        }

        $resourceLocator = substr(
            $request->getHost() . $path,
            strlen($portalInformation->getUrl())
        );

        return [$resourceLocator, $formatResult];
    }
}
