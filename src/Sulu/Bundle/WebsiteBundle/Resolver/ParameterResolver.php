<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Implements logic to resolve parameters for website rendering.
 */
class ParameterResolver implements ParameterResolverInterface
{
    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var RequestAnalyzerResolverInterface
     */
    private $requestAnalyzerResolver;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $segmentSwitchUrl;

    /**
     * @var array
     */
    private $enabledTwigAttributes;

    /**
     * ParameterResolver constructor.
     */
    public function __construct(
        StructureResolverInterface $structureResolver,
        RequestAnalyzerResolverInterface $requestAnalyzerResolver,
        WebspaceManagerInterface $webspaceManager,
        RequestStack $requestStack,
        $segmentSwitchUrl,
        array $enabledTwigAttributes = [
            'urls' => true,
        ]
    ) {
        $this->structureResolver = $structureResolver;
        $this->requestAnalyzerResolver = $requestAnalyzerResolver;
        $this->webspaceManager = $webspaceManager;
        $this->requestStack = $requestStack;
        $this->segmentSwitchUrl = $segmentSwitchUrl;
        $this->enabledTwigAttributes = $enabledTwigAttributes;
    }

    public function resolve(
        array $parameter,
        ?RequestAnalyzerInterface $requestAnalyzer = null,
        ?StructureInterface $structure = null,
        $preview = false
    ) {
        if (null !== $structure) {
            $structureData = $this->structureResolver->resolve($structure, true);
        } else {
            $structureData = [];
        }

        if (!$requestAnalyzer) {
            return \array_merge($parameter, $structureData);
        }

        $requestAnalyzerData = $this->requestAnalyzerResolver->resolve($requestAnalyzer);
        $webspace = $requestAnalyzer->getWebspace();

        if (null !== ($portal = $requestAnalyzer->getPortal())) {
            $allLocalizations = $portal->getLocalizations();
        } else {
            $allLocalizations = $webspace->getLocalizations();
        }

        $pageUrls = [];
        if (\array_key_exists('urls', $structureData)) {
            $pageUrls = $structureData['urls'];
            unset($structureData['urls']);
        }
        $localizations = [];

        foreach ($allLocalizations as $localization) {
            $locale = $localization->getLocale();

            $alternate = true;
            if (\array_key_exists($locale, $pageUrls)) {
                $url = $this->webspaceManager->findUrlByResourceLocator($pageUrls[$locale], null, $locale);
            } else {
                $alternate = false;
                $url = $this->webspaceManager->findUrlByResourceLocator('/', null, $locale);
            }

            $localizations[$locale] = [
                'locale' => $locale,
                'url' => $url,
                'country' => $localization->getCountry(),
                'alternate' => $alternate,
            ];
        }

        if ($this->enabledTwigAttributes['urls'] ?? true) {
            @trigger_deprecation('sulu/sulu', '2.2', 'Enabling the "urls" parameter is deprecated.');

            $structureData['urls'] = [];
            foreach ($localizations as $localization) {
                $structureData['urls'][$localization['locale']] = $localization['url'];
            }
        }

        $structureData['localizations'] = $localizations;

        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        $url = $request->getUri();

        $segments = [];
        foreach ($webspace->getSegments() as $segment) {
            $segmentKey = $segment->getKey();
            $segmentSwitchUrls[$segmentKey] = $this->segmentSwitchUrl . '?segment=' . $segmentKey . '&url=' . $url;
            $segments[$segmentKey] = [
                'title' => $segment->getTitle($requestAnalyzer->getCurrentLocalization()->getLocale()),
                'url' => $this->segmentSwitchUrl . '?segment=' . $segmentKey . '&url=' . $url,
            ];
        }

        $structureData['segments'] = $segments;

        return \array_merge(
            $parameter,
            $structureData,
            $requestAnalyzerData
        );
    }
}
