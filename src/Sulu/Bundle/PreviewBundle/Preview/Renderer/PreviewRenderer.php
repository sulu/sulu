<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Renderer;

use Sulu\Bundle\PreviewBundle\Preview\Events;
use Sulu\Bundle\PreviewBundle\Preview\Events\PreRenderEvent;
use Sulu\Bundle\PreviewBundle\Preview\Exception\RouteDefaultsProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TemplateNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TwigException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\UnexpectedException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\WebspaceLocalizationNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\WebspaceNotFoundException;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Renders preview responses.
 */
class PreviewRenderer implements PreviewRendererInterface
{
    /**
     * @var RouteDefaultsProviderInterface
     */
    private $routeDefaultsProvider;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var KernelFactoryInterface
     */
    private $kernelFactory;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ReplacerInterface
     */
    private $replacer;

    /**
     * @var array
     */
    private $previewDefaults;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $defaultHost;

    /**
     * @var string
     */
    private $targetGroupHeader;

    /**
     * @param RouteDefaultsProviderInterface $routeDefaultsProvider
     * @param RequestStack $requestStack
     * @param KernelFactoryInterface $kernelFactory
     * @param WebspaceManagerInterface $webspaceManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param array $previewDefaults
     * @param string $environment
     * @param string $targetGroupHeader
     */
    public function __construct(
        RouteDefaultsProviderInterface $routeDefaultsProvider,
        RequestStack $requestStack,
        KernelFactoryInterface $kernelFactory,
        WebspaceManagerInterface $webspaceManager,
        EventDispatcherInterface $eventDispatcher,
        ReplacerInterface $replacer,
        array $previewDefaults,
        $environment,
        $defaultHost,
        $targetGroupHeader = null
    ) {
        $this->routeDefaultsProvider = $routeDefaultsProvider;
        $this->requestStack = $requestStack;
        $this->kernelFactory = $kernelFactory;
        $this->webspaceManager = $webspaceManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->replacer = $replacer;
        $this->previewDefaults = $previewDefaults;
        $this->environment = $environment;
        $this->defaultHost = $defaultHost;
        $this->targetGroupHeader = $targetGroupHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function render($object, $id, $webspaceKey, $locale, $partial = false, $targetGroupId = null)
    {
        if (!$this->routeDefaultsProvider->supports(get_class($object))) {
            throw new RouteDefaultsProviderNotFoundException($object, $id, $webspaceKey, $locale);
        }

        $portalInformations = $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale(
            $webspaceKey,
            $locale,
            $this->environment
        );

        /** @var PortalInformation $portalInformation */
        $portalInformation = reset($portalInformations);

        if (!$portalInformation) {
            $portalInformation = $this->createPortalInformation($object, $id, $webspaceKey, $locale);
        }

        $webspace = $portalInformation->getWebspace();
        $localization = $webspace->getLocalization($locale);

        $query = [];
        $request = [];
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (null !== $currentRequest) {
            $query = $currentRequest->query->all();
            $request = $currentRequest->request->all();
        }

        $attributes = $this->routeDefaultsProvider->getByEntity(get_class($object), $id, $locale, $object);
        $attributes['preview'] = true;
        $attributes['partial'] = $partial;

        // get server parameters
        $server = $this->createServerAttributes($portalInformation, $currentRequest);

        $request = new Request($query, $request, $attributes, [], [], $server);
        $request->setLocale($locale);

        if ($this->targetGroupHeader && $targetGroupId) {
            $request->headers->set($this->targetGroupHeader, $targetGroupId);
        }

        // TODO Remove this event in 2.0 as it is not longer needed to set the correct theme.
        $this->eventDispatcher->dispatch(Events::PRE_RENDER, new PreRenderEvent(
            new RequestAttributes(
                [
                    'webspace' => $webspace,
                    'locale' => $locale,
                    'localization' => $localization,
                    'portal' => $portalInformation->getPortal(),
                    'portalUrl' => $portalInformation->getUrl(),
                    'resourceLocatorPrefix' => $portalInformation->getPrefix(),
                    'getParameters' => $query,
                    'postParameters' => $request,
                    'analyticsKey' => $this->previewDefaults['analyticsKey'],
                    'portalInformation' => $portalInformation,
                ]
            )
        ));

        try {
            $response = $this->handle($request);
        } catch (\Twig_Error $e) {
            // dev/test only: display also the file and line which was causing the error
            // for better debugging and faster development
            if (in_array($this->environment, ['dev', 'test'])) {
                $e->appendMessage(' (' . $e->getFile() . ' line ' . $e->getLine() . ')');
            }

            throw new TwigException($e, $object, $id, $webspace, $locale);
        } catch (\InvalidArgumentException $e) {
            throw new TemplateNotFoundException($e, $object, $id, $webspace, $locale);
        } catch (\Exception $e) {
            throw new UnexpectedException($e, $object, $id, $webspace, $locale);
        }

        return $response->getContent();
    }

    /**
     * Handles given request and returns response.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    private function handle(Request $request)
    {
        $kernel = $this->kernelFactory->create($this->environment);

        try {
            return $kernel->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
        } catch (HttpException $e) {
            if ($e->getPrevious()) {
                throw $e->getPrevious();
            }

            throw $e;
        }
    }

    /**
     * Create server attributes.
     *
     * @param PortalInformation $portalInformation
     * @param Request|null $currentRequest
     *
     * @return array
     */
    private function createServerAttributes(PortalInformation $portalInformation, Request $currentRequest = null)
    {
        // get server parameters
        $server = [];
        $host = $this->defaultHost;
        // FIXME default scheme and port should be configurable.
        $scheme = 'http';
        $port = 80;

        if ($currentRequest) {
            $server = $currentRequest->server->all();
            $scheme = $currentRequest->getScheme();
            $host = $currentRequest->getHost();
            $port = $currentRequest->getPort();
        }

        $portalUrl = $scheme . '://' . $this->replacer->replaceHost($portalInformation->getUrl(), $host);
        $portalUrlParts = parse_url($portalUrl);
        $prefixPath = isset($portalUrlParts['path']) ? $portalUrlParts['path'] : '';

        $httpHost = $portalUrlParts['host'];
        if (!in_array($port, [80, 443])) {
            $httpHost .= ':' . $port;
        }

        $server['SERVER_NAME'] = $portalUrlParts['host'];
        $server['SERVER_PORT'] = $port;
        $server['HTTP_HOST'] = $httpHost;
        $server['REQUEST_URI'] = $prefixPath . '/_sulu_preview';
        unset($server['HTTP_X_REQUESTED_WITH']); // subrequest should not be detected as ajax

        return $server;
    }

    /**
     * This creates a new portal information based on the given information. This is necessary because it is possible
     * that a webspace defines a language, which is not used in any portal. For this case we have to define our own
     * fake PortalInformation object.
     *
     * @param object $object
     * @param int $id
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return PortalInformation
     *
     * @throws WebspaceLocalizationNotFoundException
     * @throws WebspaceNotFoundException
     */
    private function createPortalInformation($object, $id, $webspaceKey, $locale)
    {
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $domain = $this->requestStack->getCurrentRequest()->getHost();

        if (!$webspace) {
            throw new WebspaceNotFoundException($object, $id, $webspaceKey, $locale);
        }

        $webspace = clone $webspace;
        $localization = $webspace->getLocalization($locale);

        if (!$localization) {
            throw new WebspaceLocalizationNotFoundException($object, $id, $webspaceKey, $locale);
        }

        $localization = clone $localization;
        $localization->setXDefault(true);
        $portal = new Portal();
        $portal->setName($webspace->getName());
        $portal->setKey($webspace->getKey());
        $portal->setWebspace($webspace);
        $portal->setXDefaultLocalization($localization);
        $portal->setLocalizations([$localization]);
        $portal->setDefaultLocalization($localization);
        $environment = new Environment();
        $url = new Url($domain, $this->environment);
        $environment->setUrls([$url]);
        $portal->setEnvironments([$environment]);
        $webspace->setPortals([$portal]);

        return new PortalInformation(RequestAnalyzer::MATCH_TYPE_FULL, $webspace, $portal, $localization, $domain);
    }
}
