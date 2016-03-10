<?php

namespace Sulu\Bundle\TestBundle\Testing\Tool;

/**
 * Testing tool for webspaces.
 */
class WebspaceTool
{
    /**
     * Generate a webspace dom document.
     *
     * @param array $config
     * @return \DOMDocument
     */
    public static function generateWebspace(array $config)
    {
        $config = self::normalizeConfig($config);

        $dom = new \DOMDocument();
        $webspaceEl = $dom->createElementNS('http://schemas.sulu.io/webspace/webspace', 'webspace');
        $webspaceEl->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'http://schemas.sulu.io/webspace/webspace http://schemas.sulu.io/webspace/webspace-1.0.xsd'
        );

        $dom->appendChild($webspaceEl);

        $nameEl = $dom->createElement('name', $config['name']);
        $webspaceEl->appendChild($nameEl);

        $keyEl = $dom->createElement('key', $config['key']);
        $webspaceEl->appendChild($keyEl);

        $localizationsEl = $dom->createElement('localizations');
        $webspaceEl->appendChild($localizationsEl);

        foreach ($config['localizations'] as $locale => $localization) {
            $localizationEl = $dom->createElement('localization');
            $localizationEl->setAttribute('language', $locale);
            if (isset($localization['country'])) {
                $localizationEl->setAttribute('country', $localization['country']);
            }
            $localizationsEl->appendChild($localizationEl);
            $localizationEl->setAttribute('shadow', $localization['shadow']);

            foreach ($localization['children'] as $child) {
                $childEl = $dom->createElement('localization');
                $childEl->setAttribute('language', $locale);
                if (isset($child['country'])) {
                    $childEl->setAttribute('country', $child['country']);
                }
                if (isset($child['shadow'])) {
                    $childEl->setAttribute('shadow', $child['shadow']);
                }
                $localizationEl->appendChild($childEl);
            }
        }

        $themeEl = $dom->createElement('theme');
        $webspaceEl->appendChild($themeEl);
        $themeKeyEl = $dom->createElement('key', $config['theme']['key']);
        $themeEl->appendChild($themeKeyEl);
        $defaultTemplatesEl = $dom->createElement('default-templates');
        $themeEl->appendChild($defaultTemplatesEl);
        foreach ($config['theme']['default_templates'] as $type => $value) {
            $defaultTemplateEl = $dom->createElement('default-template', $value);
            $defaultTemplateEl->setAttribute('type', $type);
            $defaultTemplatesEl->appendChild($defaultTemplateEl);
        }

        $navigationEl = $dom->createElement('navigation');
        $webspaceEl->appendChild($navigationEl);
        $contextsEl = $dom->createElement('contexts');
        $navigationEl->appendChild($contextsEl);

        foreach ($config['navigation'] as $contextName => $context) {
            $contextEl = $dom->createElement('context');
            $contextEl->setAttribute('key', $contextName);
            $contextsEl->appendChild($contextEl);
            $metaEl = $dom->createElement('meta');
            $contextEl->appendChild($metaEl);
            $titleEl = $dom->createElement('title', $context['title']);
            $titleEl->setAttribute('lang', $context['locale']);
            $metaEl->appendChild($titleEl);
        }

        $portalsEl = $dom->createElement('portals');
        $webspaceEl->appendChild($portalsEl);

        foreach ($config['portals'] as $name => $portal) {
            $portalEl = $dom->createElement('portal');
            $portalsEl->appendChild($portalEl);
            $nameEl = $dom->createElement('name', $portal['name']);
            $portalEl->appendChild($nameEl);
            $keyEl = $dom->createElement('key', $name);
            $portalEl->appendChild($keyEl);

            $resourceLocatorEl = $dom->createElement('resource-locator');
            $portalEl->appendChild($resourceLocatorEl);
            $strategyEl = $dom->createElement('strategy', 'tree');
            $resourceLocatorEl->appendChild($strategyEl);

            $localizationsEl = $dom->createElement('localizations');
            $portalEl->appendChild($localizationsEl);

            foreach ($portal['localizations'] as $locale => $localization) {
                $localizationEl = $dom->createElement('localization');
                $localizationsEl->appendChild($localizationEl);
                $localizationEl->setAttribute('language', $locale);
                if (isset($localization['country'])) {
                    $localizationEl->setAttribute('country', $localization['country']);
                }
                $localizationEl->setAttribute('default', $localization['default'] ? 'true' : 'false');
            }

            $environmentsEl = $dom->createElement('environments');
            $portalEl->appendChild($environmentsEl);
            foreach ($portal['environments'] as $type => $environment) {
                $environmentEl = $dom->createElement('environment');
                $environmentEl->setAttribute('type', $type);
                $environmentsEl->appendChild($environmentEl);
                $urlsEl = $dom->createElement('urls');
                $environmentEl->appendChild($urlsEl);

                foreach ($environment['urls'] as $name => $url) {
                    $urlEl = $dom->createElement('url', $name);
                    if (isset($url['language'])) {
                        $urlEl->setAttribute('language', $url['language']);
                    }
                    if (isset($url['country'])) {
                        $urlEl->setAttribute('country', $url['country']);
                    }
                    if (isset($url['redirect'])) {
                        $urlEl->setAttribute('redirect', $url['redirect']);
                    }
                    $urlsEl->appendChild($urlEl);
                }
            }
        }

        $dom->formatOutput = true;

        return $dom;
    }

    public static function normalizeConfig(array $config)
    {
        $config = array_merge([
            'key' => 'sulu_io',
            'name' => 'Sulu CMF',
            'localizations' => [ 'en' => [] ],
            'theme' => [],
            'navigation' => [ 'main' => [] ],
            'portals' => [],
        ], $config);

        foreach ($config['localizations'] as $locale => $localization) {
            $config['localizations'][$locale] = array_merge([
                'country' => null,
                'shadow' => 'none',
                'children' => [],
            ], $localization);
        }

        $config['theme'] = array_merge([
            'key' => 'default',
            'default_templates' => [
                'page' => 'default',
                'homepage' => 'default',
            ],
        ], $config['theme']);

        foreach ($config['navigation'] as $contextName => $context) {
            $config['navigation'][$contextName] = array_merge([
                'title' => 'Main Navigation',
                'locale' => 'en',
            ], $config['navigation'][$contextName]);
        }

        foreach ($config['portals'] as $key => $portal) {
            $config['portals'][$key] = array_merge([
                'name' => 'Sulu CMF',
                'resource-locator' => 'tree',
                'localizations' => [
                    'de' => [
                        'country' => 'at',
                        'default' => true,
                    ],
                ],
                'environments' => [
                    'prod' => [
                        'urls' => [
                            'sulu.at' => [ 'language' => 'de', 'country' => 'at' ],
                            'www.sulu.at' => [ 'redirect' => 'sulu.at' ],
                        ],
                    ],
                    'dev' => [
                        'urls' => [
                            'sulu.lo' => [ 'language' => 'de', 'country' => 'at' ],
                            'localhost' => [ 'language' => 'de', 'country' => 'at' ],
                            'sulu-redirect.lo' => [ 'redirect' => 'sulu.lo' ],
                        ],
                    ],
                    'phpcr' => [
                        'urls' => [
                            'sulu.lo' => [ 'language' => 'de', 'country' => 'at' ],
                            'localhost' => [ 'language' => 'de', 'country' => 'at' ],
                            'sulu-redirect.lo' => [ 'redirect' => 'sulu.lo' ],
                        ],
                    ],
                    'test' => [
                        'urls' => [
                            'sulu.lo' => [ 'language' => 'de', 'country' => 'at' ],
                            'localhost' => [ 'language' => 'de', 'country' => 'at' ],
                            'sulu-redirect.lo' => [ 'redirect' => 'sulu.lo' ],
                        ],
                    ],
                ],
            ], $config['portals']);
        }

        return $config;
    }

    public static function getConfig()
    {
        return [
            'name' => 'sulu_io',
            'title' => 'Sulu CMF',
            'locales' => [
                'en' => [
                    'shadow' => 'auto',
                    'children' => [
                        'en' => [
                            'country' => 'us',
                            'shadow' => 'none',
                        ]
                    ],
                ],
                'de' => [
                    'children' => [
                        'de' => [
                            'country' => 'at',
                        ]
                    ],
                ],
            ],
            'theme' => [],
            'navigation' => [],
            'portals' => [
                'sulu_cmf' => [],
            ],
        ];
    }
}
