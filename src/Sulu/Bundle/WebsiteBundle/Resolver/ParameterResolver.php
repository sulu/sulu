<?php

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Implements logic to resolve parameters for website rendering
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
     * ParameterResolver constructor.
     * @param StructureResolverInterface $structureResolver
     * @param RequestAnalyzerResolverInterface $requestAnalyzerResolver
     */
    public function __construct(
        StructureResolverInterface $structureResolver,
        RequestAnalyzerResolverInterface $requestAnalyzerResolver
    ) {
        $this->structureResolver = $structureResolver;
        $this->requestAnalyzerResolver = $requestAnalyzerResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        array $parameter,
        RequestAnalyzerInterface $requestAnalyzer = null,
        StructureInterface $structure = null,
        $preview = false
    ) {
        if ($structure !== null) {
            $structureData = $this->structureResolver->resolve($structure);
        } else {
            $structureData = array();
        }

        if (!$preview) {
            $requestAnalyzerData = $this->requestAnalyzerResolver->resolve($requestAnalyzer);
        } else {
            $requestAnalyzerData = $this->requestAnalyzerResolver
                ->resolveForPreview($structure->getWebspaceKey(), $structure->getLanguageCode());
        }

        if (null !== ($portal = $requestAnalyzer->getPortal())) {
            $allLocalizations = $portal->getLocalizations();
        } else {
            $allLocalizations = $requestAnalyzer->getWebspace()->getLocalizations();
        }

        $urls = array_key_exists('urls', $structureData) ? $structureData['urls'] : array();
        $localizations = array();

        foreach ($allLocalizations as $localization) {
            /** @var Localization $localization */
            $locale = $localization->getLocalization();

            if (array_key_exists($locale, $urls)) {
                $localizations[$locale] = $urls[$locale];
            } else {
                $localizations[$locale] = '';
            }
        }

        $structureData['urls'] = $localizations;

        return array_merge(
            $parameter,
            $structureData,
            $requestAnalyzerData
        );
    }
}
