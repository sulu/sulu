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
use Sulu\Component\Webspace\CustomUrl;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Exception\InvalidTemplateException;
use Sulu\Component\Webspace\Loader\Exception\InvalidAmountOfDefaultErrorTemplateException;
use Sulu\Component\Webspace\Loader\Exception\InvalidErrorTemplateException;
use Sulu\Component\Webspace\Navigation;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Webspace;

class WebspaceCollectionBuilder
{
    /**
     * @param array<mixed> $configuration
     */
    public function __construct(
        private string $webspaceCollectionClass,
        private PortalInformationBuilder $portalInformationBuilder,
        private array $configuration = []
    ) {
    }

    public function build(): WebspaceCollection
    {
        $webspaceRefs = [];
        $portalRefs = [];
        $segmentRefs = [];

        foreach ($this->configuration as $webspaceKey => $webspaceConfiguration) {
            $webspaceRefs[$webspaceKey] = $this->buildWebspace(
                $webspaceConfiguration,
                $portalRefs,
                $segmentRefs,
            );
        }

        return new $this->webspaceCollectionClass(
            $webspaceRefs,
            $portalRefs,
            $this->portalInformationBuilder->dumpAndClear(),
        );
    }

    /**
     * @param array<string, PortalInformation> $portalRefs
     * @param array<string, Segment> $segmentRefs
     * @param array<int,mixed> $webspaceConfiguration
     */
    protected function buildWebspace(
        array $webspaceConfiguration,
        array &$portalRefs,
        array &$segmentRefs,
    ): Webspace {
        $webspaceKey = $webspaceConfiguration['key'];

        $webspace = new Webspace();
        $webspace->setKey($webspaceKey);
        $webspace->setName($webspaceConfiguration['name']);

        $securityConfiguration = $webspaceConfiguration['security'] ?? [];
        if ([] !== $securityConfiguration) {
            $security = new Security();
            $security->setSystem($securityConfiguration['system']);
            $security->setPermissionCheck($securityConfiguration['permission_check']);

            $webspace->setSecurity($security);
        }

        foreach ($webspaceConfiguration['localizations']['localization'] as $localizationConfiguration) {
            $localization = $this->buildLocalization($localizationConfiguration);

            foreach ($localizationConfiguration['localization'] ?? [] as $childLocalization) {
                $childLocalization = $this->buildLocalization($childLocalization);
                $childLocalization->setParent($localization);

                $localization->addChild($childLocalization);
            }

            $webspace->addLocalization($localization);
        }

        $this->buildSegments($webspaceConfiguration, $webspace, $segmentRefs);
        $this->buildTemplates($webspaceConfiguration, $webspace);
        $this->buildNavgiation($webspaceConfiguration, $webspace);

        $webspace->setResourceLocatorStrategy($webspaceConfiguration['resource_locator']['strategy']);

        foreach ($webspaceConfiguration['portals']['portal'] as $portalConfiguration) {
            $portal = $this->buildPortal($portalConfiguration, $webspace);

            $portalRefs[$portalConfiguration['key']] = $portal;
            $webspace->addPortal($portal);
        }

        return $webspace;
    }

    /**
     * @param array<int,mixed> $webspaceConfiguration
     */
    public function buildNavgiation(array $webspaceConfiguration, Webspace $webspace): void
    {
        $navigation = new Navigation();

        foreach ($webspaceConfiguration['navigation']['contexts'] ?? [] as $contextConfigurations) {
            foreach ($contextConfigurations as $contextConfiguration) {
                if (\array_key_exists('titles', $contextConfiguration['meta'] ?? [])) {
                    $meta = ['title' => $contextConfiguration['meta']['titles']];
                } else {
                    $meta = $contextConfiguration['meta'];
                }
                $navigation->addContext(new NavigationContext($contextConfiguration['key'], $meta));
            }
        }
        $webspace->setNavigation($navigation);
    }

    /**
     * @param array<int,mixed> $webspaceConfiguration
     * @param array<int,mixed> $segmentRefs
     */
    protected function buildSegments(array $webspaceConfiguration, Webspace $webspace, array &$segmentRefs): void
    {
        foreach ($webspaceConfiguration['segments']['segment'] ?? [] as $segmentConfiguration) {
            $segment = new Segment();
            $segment->setKey($segmentConfiguration['key']);
            $segment->setMetadata($segmentConfiguration['metadata'] ?? []);
            $segment->setDefault($segmentConfiguration['default']);

            $webspace->addSegment($segment);

            $segmentRefs[$webspace->getKey() . '_' . $segmentConfiguration['key']] = $segment;
        }
    }

    /**
     * @param array<int,mixed> $portalConfiguration
     */
    protected function buildPortal(
        array $portalConfiguration,
        Webspace $webspace,
    ): Portal {
        $portal = new Portal();
        $portal->setName($portalConfiguration['name']);
        $portal->setKey($portalConfiguration['key']);
        $portal->setWebspace($webspace);

        $localizationConfigurations = $portalConfiguration['localizations']['localization'] ?? [];
        if ([] === $localizationConfigurations) {
            $portal->setLocalizations($webspace->getLocalizations());
        } else {
            foreach ($localizationConfigurations as $localizationConfiguration) {
                $localization = new Localization($localizationConfiguration['language']);
                $localization->setCountry($localizationConfiguration['country']);
                $localization->setDefault($localizationConfiguration['default']);

                $portal->addLocalization($localization);
            }
        }

        foreach ($portalConfiguration['environments']['environment'] ?? [] as $environmentConfiguration) {
            $portal->addEnvironment($this->buildEnvironment(
                $environmentConfiguration,
                $portal,
                $webspace,
            ));
        }

        return $portal;
    }

    /**
     * @param array<int,mixed> $webspaceConfiguration
     */
    protected function buildTemplates(array $webspaceConfiguration, Webspace $webspace): void
    {
        $webspace->setTheme($webspaceConfiguration['theme']);

        foreach ($webspaceConfiguration['templates']['template'] ?? [] as $type => $template) {
            $webspace->addTemplate($type, $template);
        }

        foreach ($webspaceConfiguration['excluded_templates']['excluded_template'] ?? [] as $excludedTemplate) {
            $webspace->addExcludedTemplate($excludedTemplate);
        }

        foreach ($webspaceConfiguration['default_templates']['default_template'] as $type => $defaultTemplate) {
            if ('homepage' == $type) {
                $type = 'home';
            }
            if (\in_array($defaultTemplate, $webspace->getExcludedTemplates())) {
                throw new InvalidTemplateException($webspace, $defaultTemplate);
            }
            $webspace->addDefaultTemplate($type, $defaultTemplate);
        }

        $this->buildErrorTemplates($webspaceConfiguration, $webspace);
    }

    /**
     * @param array<int,mixed> $webspaceConfiguration
     */
    protected function buildErrorTemplates(array $webspaceConfiguration, Webspace $webspace): void
    {
        $defaultErrorTemplateCount = 0;
        foreach ($webspaceConfiguration['error_templates'] ?? [] as $errorTemplate) {
            if (null !== $errorTemplate['code']) {
                $webspace->addTemplate('error-' . $errorTemplate['code'], $errorTemplate['value']);
            } elseif ($errorTemplate['default']) {
                $webspace->addTemplate('error', $errorTemplate['value']);
                ++$defaultErrorTemplateCount;
            } else {
                throw new InvalidErrorTemplateException($errorTemplate['value'], $webspace->getKey());
            }
        }

        // only one or none default error-template is legal
        if ($defaultErrorTemplateCount > 1) {
            throw new InvalidAmountOfDefaultErrorTemplateException($webspace->getKey());
        }
    }

    /**
     * @param array<int,mixed> $environmentConfiguration
     */
    protected function buildEnvironment(
        array $environmentConfiguration,
        Portal $portal,
        Webspace $webspace,
    ): Environment {
        $environment = new Environment();
        $environment->setType($environmentConfiguration['type']);

        foreach ($environmentConfiguration['custom_urls']['custom_url'] ?? [] as $customUrl) {
            $url = new CustomUrl(\rtrim($customUrl, '/'));

            $this->portalInformationBuilder->addCustomUrl($url, $environment, $portal);
            $environment->addCustomUrl($url);
        }

        foreach ($environmentConfiguration['urls']['url'] ?? [] as $urlConfiguration) {
            $url = new Url(\rtrim($urlConfiguration['value'], '/'));
            $url->setLanguage($urlConfiguration['language']);
            $url->setCountry($urlConfiguration['country'] ?? null);
            $url->setSegment($urlConfiguration['segment'] ?? null);
            $url->setRedirect($urlConfiguration['redirect'] ?? null);
            $url->setMain($urlConfiguration['main'] ?? false);

            $environment->addUrl($url);

            $this->portalInformationBuilder->addUrl($url, $environment, $portal);
        }

        return $environment;
    }

    /**
     * @param array<int,mixed> $localizationConfiguration
     */
    protected function buildLocalization(array $localizationConfiguration): Localization
    {
        $localization = new Localization($localizationConfiguration['language']);
        $localization->setCountry($localizationConfiguration['country'] ?? null);
        $localization->setShadow($localizationConfiguration['shadow'] ?? null);
        $localization->setDefault($localizationConfiguration['default']);

        return $localization;
    }
}
