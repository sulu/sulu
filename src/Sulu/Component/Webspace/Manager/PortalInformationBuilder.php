<?php

declare(strict_types=1);

namespace Sulu\Component\Webspace\Manager;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Sulu\Component\Webspace\Url;

class PortalInformationBuilder
{
    /**
     * @var array<string, array<string, PortalInformation>> $portalInformation
    */
    private array $portalInformations = [];

    public function __construct(private ReplacerInterface $urlReplacer) {
    }

    public function addUrl(Url $url, Environment $environment,  Portal $portal): void
    {
        $urlAddress = $url->getUrl();
        if (null == $url->getRedirect()) {
            $this->buildUrls($portal, $environment, $url, $urlAddress);
        } else {
            // create the redirect
            $this->buildUrlRedirect(
                $portal->getWebspace(),
                $environment,
                $portal,
                $urlAddress,
                $url
            );
        }
    }

    public function addCustomUrl(Url $url, Environment $environment, Portal $portal): void
    {
        $urlAddress = $url->getUrl();

        $this->portalInformation[$environment->getType()][$urlAddress] = new PortalInformation(
            type: RequestAnalyzerInterface::MATCH_TYPE_WILDCARD,
            webspace: $portal->getWebspace(),
            portal: $portal,
            localization: null,
            url: $urlAddress,
            urlExpression: $urlAddress,
            priority: 1
        );

    }

    private function buildUrlRedirect(Webspace $webspace, Environment $environment, Portal $portal, string $urlAddress, Url $url): void {
        $this->portalInformations[$environment->getType()][$urlAddress] = new PortalInformation(
            type: RequestAnalyzerInterface::MATCH_TYPE_REDIRECT,
            webspace: $webspace,
            portal: $portal,
            url: $urlAddress,
            redirect: $url->getRedirect(),
            main: $url->isMain(),
            urlExpression: $url->getUrl(),
            priority: $this->urlReplacer->hasHostReplacer($urlAddress) ? 4 : 9
        );
    }
    /**
     * @param array<int,mixed> $replacers
     */
    private function buildUrlFullMatch(Portal $portal, Environment $environment, array $replacers, string $urlAddress, Localization $localization, Url $url): void {
        $urlResult = $this->generateUrlAddress($urlAddress, $replacers);

        $this->portalInformations[$environment->getType()][$urlResult] = new PortalInformation(
            type: RequestAnalyzerInterface::MATCH_TYPE_FULL,
            webspace: $portal->getWebspace(),
            portal: $portal,
            localization: $localization,
            url: $urlResult,
            main: $url->isMain(),
            urlExpression: $url->getUrl(),
            priority: $this->urlReplacer->hasHostReplacer($urlResult) ? 5 : 10
        );
    }

    private function buildUrlPartialMatch(Portal $portal, Environment $environment, string $urlAddress, Url $url): void {
        $replacers = [];

        $urlResult = $this->urlReplacer->cleanup(
            $urlAddress,
            [
                ReplacerInterface::REPLACER_LANGUAGE,
                ReplacerInterface::REPLACER_COUNTRY,
                ReplacerInterface::REPLACER_LOCALIZATION,
                ReplacerInterface::REPLACER_SEGMENT,
            ]
        );
        $urlRedirect = $this->generateUrlAddress($urlAddress, $replacers);

        if ($this->validateUrlPartialMatch($urlResult, $environment)) {
            $this->portalInformations[$environment->getType()][$urlResult] = new PortalInformation(
                type: RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                webspace: $portal->getWebspace(),
                portal: $portal,
                url: $urlResult,
                redirect: $urlRedirect,
                main: false, // partial matches cannot be main
                urlExpression: $url->getUrl(),
                priority: $this->urlReplacer->hasHostReplacer($urlResult) ? 4 : 9
            );
        }
    }

    private function buildUrls(Portal $portal, Environment $environment, Url $url, string $urlAddress): void {
        if ($url->getLanguage()) {
            $language = $url->getLanguage();
            $country = $url->getCountry();
            $locale = $language . ($country ? '_' . $country : '');

            $replacers = [
                ReplacerInterface::REPLACER_LANGUAGE => $language,
                ReplacerInterface::REPLACER_COUNTRY => $country,
                ReplacerInterface::REPLACER_LOCALIZATION => $locale,
            ];

            $this->buildUrlFullMatch(
                $portal,
                $environment,
                $replacers,
                $urlAddress,
                $portal->getLocalization($locale),
                $url
            );
        } else {
            // create all the urls for every localization combination
            foreach ($portal->getLocalizations() as $localization) {
                $language = $url->getLanguage() ? $url->getLanguage() : $localization->getLanguage();
                $country = $url->getCountry() ? $url->getCountry() : $localization->getCountry();

                $replacers = [
                    ReplacerInterface::REPLACER_LANGUAGE => $language,
                    ReplacerInterface::REPLACER_COUNTRY => $country,
                    ReplacerInterface::REPLACER_LOCALIZATION => $localization->getLocale(Localization::DASH),
                ];

                $this->buildUrlFullMatch(
                    $portal,
                    $environment,
                    $replacers,
                    $urlAddress,
                    $localization,
                    $url
                );
            }
        }

        $this->buildUrlPartialMatch(
            $portal,
            $environment,
            $urlAddress,
            $url
        );
    }

    /**
     * @param array<int,mixed> $replacers
     */
    private function generateUrlAddress(string $pattern, array $replacers): string
    {
        foreach ($replacers as $replacer => $value) {
            $pattern = $this->urlReplacer->replace($pattern, $replacer, $value);
        }

        return $pattern;
    }

    private function validateUrlPartialMatch(string $urlResult, Environment $environment): bool
    {
        return
            // only valid if there is no full match already
            !\array_key_exists($urlResult, $this->portalInformations[$environment->getType()])
            // check if last character is no dot
            && '.' != \substr($urlResult, -1);
    }

    /**
     * @return array<int,array>
     */
    public function dumpAndClear(): array
    {
        $portalInformations = $this->portalInformations;

        foreach($portalInformations as &$portalInformation) {
            \uksort(
                $portalInformation,
                fn ($a, $b) => \strlen($a) < \strlen($b) ? 1 : -1
            );
        }

        unset($this->portalInformations);

        return $portalInformations;
    }

}
