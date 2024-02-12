<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Provider\LocalizationProviderInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;

interface WebspaceManagerInterface extends LocalizationProviderInterface
{
    public function findWebspaceByKey(?string $key): ?Webspace;

    public function findPortalByKey(?string $key): ?Portal;

    /**
     * Returns the portal with the given url (which has not necessarily to be the main url).
     */
    public function findPortalInformationByUrl(string $url, ?string $environment = null): ?PortalInformation;

    /**
     * Returns all portal which matches the given url (which has not necessarily to be the main url).
     *
     * @return PortalInformation[]
     */
    public function findPortalInformationsByUrl(string $url, ?string $environment = null): array;

    /**
     * Returns all portal which matches a given host (optional includes also subdomains).
     *
     * @return PortalInformation[]
     */
    public function findPortalInformationsByHostIncludingSubdomains(string $host, ?string $environment = null): array;

    /**
     * Returns all portal which matches the given webspace-key and locale.
     *
     * @return PortalInformation[]
     */
    public function findPortalInformationsByWebspaceKeyAndLocale(
        string $webspaceKey,
        string $locale,
        ?string $environment = null
    ): array;

    /**
     * Returns all portal which matches the given portal-key and locale.
     *
     * @return PortalInformation[]
     */
    public function findPortalInformationsByPortalKeyAndLocale(
        string $portalKey,
        string $locale,
        ?string $environment = null
    ): array;

    /**
     * Returns all possible urls for resourcelocator.
     *
     * @return string[]
     */
    public function findUrlsByResourceLocator(
        string $resourceLocator,
        ?string $environment,
        string $languageCode,
        ?string $webspaceKey = null,
        ?string $domain = null,
        ?string $scheme = null
    ): array;

    /**
     * Returns the main url for resourcelocator.
     */
    public function findUrlByResourceLocator(
        ?string $resourceLocator,
        ?string $environment,
        string $languageCode,
        ?string $webspaceKey = null,
        ?string $domain = null,
        ?string $scheme = null
    ): ?string;

    /**
     * @return Portal[]
     */
    public function getPortals(): array;

    /**
     * @return string[]
     */
    public function getUrls(?string $environment = null): array;

    /**
     * @return PortalInformation[]
     */
    public function getPortalInformations(?string $environment = null): array;

    /**
     * @return PortalInformation[]
     */
    public function getPortalInformationsByWebspaceKey(?string $environment, string $webspaceKey): array;

    public function getWebspaceCollection(): WebspaceCollection;

    /**
     * For all available webspaces provide all their possible locales. Moreover
     * for each website the default locale is provided. The default locales of
     * the webspaces are always on the first position.
     *
     * The first dimension is the webspace key, the second dimension is the locale code. Using it like this:
     * $webspaceLocalesByCode['webspace-key']['en_us'] will return you the localization for that.
     *
     * @return array<string, array<string, Localization>>
     */
    public function getAllLocalesByWebspaces(): array;
}
