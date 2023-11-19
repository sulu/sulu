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

class WebspaceCollectionBuilder
{
    public function __construct(private array $configuration = [])
    {
    }

    public function build(): WebsiteCollection
    {
        $webspaceRefs = [];
        $portalRefs = [];
        $localizationRefs = [];
        $segmentRefs = [];
        $portalInformationRefs = [];

        foreach ($this->configuration['webspaces'] as $webspaceKey => $webspaceConfiguration) {
            $webspaceRefs[$webspaceKey] = $this->buildWebspace(
                $webspaceConfiguration,
                $portalRefs,
                $localizationRefs,
                $segmentRefs,
            );
        }

        /**

        {% for environmentKey, environment in collection.portalInformations %}
                {% for portalInformation in environment %}
                        $portalInformationRefs['{{ environmentKey }}']['{{ portalInformation.url }}'] = new PortalInformation(
                            {{ portalInformation.type }},
                            $webspaceRefs['{{ portalInformation.webspace }}'],
                            {% if portalInformation.portal %}
                                        $portalRefs['{{ portalInformation.portal }}'],
                            {% else %}
                                        null,
                            {% endif %}
                            {% if portalInformation.localization %}
                                        $localizationRefs['{{ portalInformation.webspace }}_{{ portalInformation.localization.localization }}'],
                            {% else %}
                                        null,
                            {% endif %}
                            '{{ portalInformation.url }}',
                            {% if portalInformation.segment %}
                                        $segmentRefs['{{ portalInformation.webspace }}_{{ portalInformation.segment }}'],
                            {% else %}
                                        null,
                            {% endif %}
                            portalInformation.redirect ?: null,
                            portalInformation.main,
                            portalInformation.urlExpression
                            portalInformation.priority
                        );

                {% endfor %}
        {% endfor %}
         */
                        
        $webspaceCollection = new WebspaceCollection();
        $webspaceCollection->setWebspaces($webspaceRefs);
        $webspaceCollection->setPortals($portalRefs);
        $webspaceCollection->setPortalInformations($portalInformationRefs);

        return $webspaceCollection;
    }

    protected function buildWebspace(
        array $webspaceConfiguration,
        array &$portalRefs,
        array &$localizationRefs,
        array &$segmentRefs,
    ): Webspace {
        $webspaceKey = $webspaceConfiguration['key'];

        $webspace = new Webspace();
        $webspace->setKey($webspaceKey);
        $webspace->setName($webspaceConfiguration['name']);

        if (($securityConfiguration = $webspaceConfiguration['security']) !== null) {
            $security = new Security();
            $security->setSystem($securityConfiguration['systen']);
            $security->setPermissionCheck($securityConfiguration['permissionCheck']);

            $webspace->setSecurity($security);
        }

        foreach ($webspaceConfiguration['localizations'] as $localizationConfiguration) {
            $localization = [$this->buildLocalization($localizationConfiguration)];

            foreach ($localizationConfiguration['children'] as $childLocalization) {
                $this->buildLocalizationTree($childLocalization, $localization, 1, 0, $webspaceKey);
            }

            $localizationRefs[$webspaceKey.'_'.$localizationConfiguration['localization']] = $localization[0];

            $webspace->addLocalization($localization[0]);
        }
       
        foreach ($webspaceConfiguration['segments'] as $segmentConfiguration) {
            $segment = new Segment();
            $segment->setKey($segmentConfiguration['key']);
            $segment->setMetadata($segmentConfiguration['metadata']);
            $segment->setDefault($segmentConfiguration['default']);

            $webspace->addSegment($segment);

            $segmentRefs[$webspaceKey.'_'.$segmentConfiguration['key']] = $segment;
        }

        $this->buildTemplates($webspace, $webspaceConfiguration);

        $navigation = new Navigation();
        foreach ($webspaceConfiguration['navigation']['context'] as $contextConfiguration) {
            $navigation->addContext(new NavigationContext($contextConfiguration['key'], $contextConfiguration['metadata']));
        }
        $webspace->setNavigation($navigation);

        $webspace->setResourceLocatorStrategy('{{ webspace.resourceLocator.strategy }}');

        foreach ($webspaceConfiguration['portals'] as $portalConfiguration) {
            $portal = $this->buildPortal($portalConfiguration);

            $portalRefs[$portalConfiguration['key']] = $portal;
            $webspace->addPortal($portal);
        }

        return $webspace;
    }

    protected function buildPortal(array $portalConfiguration): Portal 
    {
        $portal = new Portal();
        $portal->setName($portalConfiguration['name']);
        $portal->setKey($portalConfiguration['key']);
        $portal->setWebspace($webspace);

        foreach ($portalConfiguration['localizations'] as $localizationConfiguration) {
            $localization = new Localization();
            $localization->setLanguage($localizationConfiguration['language']);
            $localization->setCountry($localizationConfiguration['country']);
            $localization->setDefault($localizationConfiguration['default']);

            $portal->addLocalization($localization);
        }

        foreach ($portalConfiguration['environments'] as $environmentConfiguration) {
            $portal->addEnvironment($this->buildEnvironment($environmentConfiguration));
        }

        return $portal;
    }

    protected function buildTemplates(Webspace $webspace, array $webspaceConfiguration): void 
    {
        $webspace->setTheme($webspaceConfiguration['theme']);

        foreach ($webspaceConfiguration['templates'] as $type => $template) {
            $webspace->addTemplate($type, $template);
        }   
        foreach ($webspaceConfiguration['defaultTemplates'] as $type => $defaultTemplate) {
            $webspace->addDefaultTemplate($type, $defaultTemplate);
        }   
        foreach ($webspaceConfiguration['excludedTemplates'] as $excludedTemplate) {
            $webspace->addExcludedTemplate($excludedTemplate);
        }   
    }

    protected function buildEnvironment(array $environmentConfiguration): Environment 
    {
        $environment = new Environment();
        $environment->setType($environmentConfiguration['type']);

        foreach ($environmentConfiguration['urls'] as $url) {
            $url = new Url();
            $url->setUrl($url['url']);
            $url->setLanguage($url['language']);
            $url->setCountry($url['country']);
            $url->setSegment($url['segment']);
            $url->setRedirect($url['redirect']);
            $url->setMain($url['main']);

            $environment->addUrl($url);
        }

        foreach ($environmentConfiguration['customUrls'] as $customUrl) {
            $environment->addCustomUrl(new CustomUrl($customUrl['url']));
        }
        
        return $environment;
    }

    protected function buildLocalization(array $localizationConfiguration): Localization
    {
        $localization = new Localization();
        $localization->setLanguage($localizationConfiguration['language']);
        $localization->setCountry($localizationConfiguration['country']);
        $localization->setShadow($localizationConfiguration['shadow']);
        $localization->setDefault($localizationConfiguration['default']);

        return $localization;
    }

    private function buildLocalizationTree(
        array $localizationConfiguration, 
        array &$localization, 
        int $currentIndex, 
        int $parentIndex, 
        string $webspaceKey
    ): void {
        $localization[$currentIndex] = $this->buildLocalization($localizationConfiguration);
        $localization[$currentIndex]->setParent($localization[$parent]);

        $localizationRefs[$webspaceKey.'_'.$localizationConfiguration['localization']] = $localization[$currentIndex];

        foreach ($localizationConfiguration['children'] as $childLocalization) {
            $this->buildLocalizationTree($childLocalization, $localization, $currentIndex + 1, $currentIndex)
        }

        $localization[$currentIndex - 1]->addChild($localization[$currentIndex]);
    }
}
