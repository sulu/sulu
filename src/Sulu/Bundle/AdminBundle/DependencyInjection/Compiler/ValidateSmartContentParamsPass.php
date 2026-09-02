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

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Util\Exception\XmlParsingException;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
final class ValidateSmartContentParamsPass implements CompilerPassInterface
{
    private const DEPRECATED_PARAMS = ['types', 'structureTypes'];

    private const GROUP_PROVIDERS = [
        'articles',
        'articles_page_tree',
        'snippets',
    ];

    private const TEMPLATE_NAMESPACE = 'http://schemas.sulu.io/template/template';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('sulu_admin.templates.configuration')) {
            return;
        }

        /** @var array<string, array{default_type: string|null, directories: array<string>}> $configuration */
        $configuration = $container->getParameter('sulu_admin.templates.configuration');

        $errors = [];

        foreach ($configuration as $config) {
            $directories = \array_filter(
                $config['directories'],
                static fn (string $dir): bool => \file_exists($dir),
            );
            if ([] === $directories) {
                continue;
            }

            foreach ((new Finder())->in($directories)->name('*.xml') as $file) {
                $container->addResource(new FileResource($file->getPathname()));
                $this->collectErrorsFromFile($file->getPathname(), $errors);
            }
        }

        if ([] !== $errors) {
            throw new \RuntimeException(
                "Deprecated smart_content param names found in template XML files:\n\n"
                . \implode("\n", $errors)
                . "\n\nPlease rename the deprecated params to their replacements."
            );
        }
    }

    /**
     * @param list<string> $errors
     */
    private function collectErrorsFromFile(string $filePath, array &$errors): void
    {
        try {
            $document = XmlUtils::loadFile($filePath);
        } catch (XmlParsingException|\InvalidArgumentException) {
            return;
        }

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('t', self::TEMPLATE_NAMESPACE);

        $templateKeyNodes = $xpath->query('/t:template/t:key');
        $templateKeyNode = false !== $templateKeyNodes ? $templateKeyNodes->item(0) : null;
        $templateKey = null !== $templateKeyNode ? $templateKeyNode->nodeValue ?? '' : '';

        $smartContentProperties = $xpath->query('//t:property[@type="smart_content"]') ?: [];

        foreach ($smartContentProperties as $property) {
            \assert($property instanceof \DOMElement);
            $this->collectErrorsFromProperty($xpath, $property, $templateKey, $filePath, $errors);
        }
    }

    /**
     * @param list<string> $errors
     */
    private function collectErrorsFromProperty(
        \DOMXPath $xpath,
        \DOMElement $property,
        string $templateKey,
        string $filePath,
        array &$errors,
    ): void {
        $propertyName = $property->getAttribute('name');

        $provider = null;
        $deprecated = [];

        foreach ($xpath->query('t:params/t:param', $property) ?: [] as $param) {
            \assert($param instanceof \DOMElement);
            $name = $param->getAttribute('name');
            $value = $param->getAttribute('value');

            if ('provider' === $name) {
                $provider = $value;
            }

            if (\in_array($name, self::DEPRECATED_PARAMS, true)) {
                $deprecated[] = $name;
            }
        }

        $isGroupProvider = null !== $provider && \in_array($provider, self::GROUP_PROVIDERS, true);

        foreach ($deprecated as $deprecatedName) {
            $replacement = match ($deprecatedName) {
                'types' => $isGroupProvider ? 'groups' : 'templateKeys',
                'structureTypes' => 'templateKeys',
            };

            $errors[] = \sprintf(
                '- Template "%s", property "%s": param "%s" is deprecated, rename to "%s" (file: %s)',
                $templateKey,
                $propertyName,
                $deprecatedName,
                $replacement,
                $filePath,
            );
        }
    }
}
