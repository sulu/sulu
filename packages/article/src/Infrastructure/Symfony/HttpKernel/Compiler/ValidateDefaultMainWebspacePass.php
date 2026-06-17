<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Infrastructure\Symfony\HttpKernel\Compiler;

use Sulu\Component\Localization\Localization;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Util\Exception\XmlParsingException;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

/**
 * @internal this class is not part of the public API and should only be called by the Symfony framework classes
 */
final class ValidateDefaultMainWebspacePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $configDir = $this->resolveWebspaceConfigDir($container);
        if (null === $configDir) {
            return;
        }

        $container->addResource(new DirectoryResource($configDir, '/\.xml$/'));

        $localizations = $this->readWebspaceLocalizations($configDir);
        $webspaceKeys = \array_keys($localizations);
        $defaultMainWebspace = $this->getConfiguredDefaultMainWebspace($container);

        if ([] === $defaultMainWebspace) {
            if (\count($webspaceKeys) > 1) {
                throw new InvalidConfigurationException(\sprintf(
                    'The "sulu_article.default_main_webspace" option must be configured when more than one webspace '
                    . 'exists (found %d webspaces in "%s"). Set it to one of your webspace keys.',
                    \count($webspaceKeys),
                    $configDir,
                ));
            }

            return;
        }

        if ([] === $webspaceKeys) {
            return;
        }

        foreach ($defaultMainWebspace as $webspaceKey) {
            if (!\in_array($webspaceKey, $webspaceKeys, true)) {
                throw new InvalidConfigurationException(\sprintf(
                    'The "sulu_article.default_main_webspace" option is set to the unknown webspace "%s". '
                    . 'Configure it with one of the existing webspace keys: "%s".',
                    $webspaceKey,
                    \implode('", "', $webspaceKeys),
                ));
            }
        }

        // Every authorable locale must map to a webspace that supports it, so a
        // non-pinned article never receives an incompatible mainWebspace at runtime.
        foreach ($this->collectLocales($localizations) as $locale) {
            $resolved = $this->resolveConfiguredWebspaceForLocale($defaultMainWebspace, $locale, $webspaceKeys);

            if (null === $resolved) {
                throw new InvalidConfigurationException(\sprintf(
                    'The locale "%s" has no "sulu_article.default_main_webspace" mapping. Add a per-locale entry '
                    . '"%s" or a "default" entry pointing to a webspace that supports this locale.',
                    $locale,
                    $locale,
                ));
            }

            if (!\in_array($locale, $localizations[$resolved] ?? [], true)) {
                throw new InvalidConfigurationException(\sprintf(
                    'The "sulu_article.default_main_webspace" maps locale "%s" to webspace "%s", which does not '
                    . 'support that locale. Map "%s" to a webspace whose localizations include it.',
                    $locale,
                    $resolved,
                    $locale,
                ));
            }
        }
    }

    /**
     * Mirrors WebspaceSettingsConfigurationResolver precedence.
     *
     * @param array<string, string> $defaultMainWebspace
     * @param list<string> $webspaceKeys
     */
    private function resolveConfiguredWebspaceForLocale(array $defaultMainWebspace, string $locale, array $webspaceKeys): ?string
    {
        if (\array_key_exists($locale, $defaultMainWebspace)) {
            return $defaultMainWebspace[$locale];
        }

        if (\array_key_exists('default', $defaultMainWebspace)) {
            return $defaultMainWebspace['default'];
        }

        if (1 === \count($webspaceKeys)) {
            return $webspaceKeys[0];
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function getConfiguredDefaultMainWebspace(ContainerBuilder $container): array
    {
        if (!$container->hasParameter('sulu_article.default_main_webspace')) {
            return [];
        }

        /** @var array<string, string> $defaultMainWebspace */
        $defaultMainWebspace = $container->getParameter('sulu_article.default_main_webspace');

        return $defaultMainWebspace;
    }

    private function resolveWebspaceConfigDir(ContainerBuilder $container): ?string
    {
        if (!$container->hasParameter('sulu_core.webspace.config_dir')) {
            return null;
        }

        $configDir = $container->getParameterBag()->resolveValue(
            $container->getParameter('sulu_core.webspace.config_dir'),
        );

        if (!\is_string($configDir) || !\is_dir($configDir)) {
            return null;
        }

        return $configDir;
    }

    /**
     * Maps each webspace key to its declared locales (`language` or
     * `language_country`, matching Localization::getLocale()).
     *
     * @return array<string, list<string>>
     */
    private function readWebspaceLocalizations(string $configDir): array
    {
        $localizations = [];
        foreach ((new Finder())->in($configDir)->files()->name('*.xml') as $file) {
            try {
                $document = XmlUtils::loadFile($file->getPathname());
            } catch (XmlParsingException|\InvalidArgumentException) {
                continue;
            }

            $xpath = new \DOMXPath($document);

            $keyNodes = $xpath->query('/*[local-name()="webspace"]/*[local-name()="key"]');
            $key = false !== $keyNodes && null !== $keyNodes->item(0) ? \trim((string) $keyNodes->item(0)->nodeValue) : '';

            if ('' === $key) {
                continue;
            }

            $locales = [];
            $localizationNodes = $xpath->query('/*[local-name()="webspace"]//*[local-name()="localization"]');
            if (false !== $localizationNodes) {
                foreach ($localizationNodes as $node) {
                    if (!$node instanceof \DOMElement) {
                        continue;
                    }

                    $language = \trim($node->getAttribute('language'));
                    if ('' === $language) {
                        continue;
                    }

                    $country = \trim($node->getAttribute('country'));
                    $locales[] = (new Localization($language, '' !== $country ? $country : null))->getLocale();
                }
            }

            $localizations[$key] = \array_values(\array_unique($locales));
        }

        return $localizations;
    }

    /**
     * @param array<string, list<string>> $localizations
     *
     * @return list<string>
     */
    private function collectLocales(array $localizations): array
    {
        $locales = [];
        foreach ($localizations as $webspaceLocales) {
            foreach ($webspaceLocales as $locale) {
                $locales[$locale] = true;
            }
        }

        return \array_keys($locales);
    }
}
