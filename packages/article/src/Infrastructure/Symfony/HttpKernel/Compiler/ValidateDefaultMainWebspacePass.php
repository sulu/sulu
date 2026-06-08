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

        $webspaceKeys = $this->readWebspaceKeys($configDir);
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
     * @return list<string>
     */
    private function readWebspaceKeys(string $configDir): array
    {
        $webspaceKeys = [];
        foreach ((new Finder())->in($configDir)->files()->name('*.xml') as $file) {
            try {
                $document = XmlUtils::loadFile($file->getPathname());
            } catch (XmlParsingException|\InvalidArgumentException) {
                continue;
            }

            $keyNodes = (new \DOMXPath($document))->query('/*[local-name()="webspace"]/*[local-name()="key"]');
            $key = false !== $keyNodes && null !== $keyNodes->item(0) ? (string) $keyNodes->item(0)->nodeValue : '';

            if ('' !== $key) {
                $webspaceKeys[] = $key;
            }
        }

        return $webspaceKeys;
    }
}
