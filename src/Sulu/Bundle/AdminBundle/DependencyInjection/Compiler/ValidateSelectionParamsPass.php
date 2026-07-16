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
final class ValidateSelectionParamsPass implements CompilerPassInterface
{
    /**
     * Outdated param names per selection field type, mapped to their replacement.
     *
     * @var array<string, array<string, string>>
     */
    private const OUTDATED_PARAMS = [
        'snippet_selection' => ['types' => 'templateKeys'],
        'single_snippet_selection' => ['types' => 'templateKeys'],
        'article_selection' => ['types' => 'groups', 'templates' => 'templateKeys'],
        'single_article_selection' => ['types' => 'groups', 'templates' => 'templateKeys'],
        'page_selection' => ['types' => 'templateKeys'],
        'single_page_selection' => ['types' => 'templateKeys'],
    ];

    private const OUTDATED_MESSAGE = '- Key "%s", property "%s": param "%s" is not supported, use "%s" (file: %s)';

    private const TEMPLATE_NAMESPACE = 'http://schemas.sulu.io/template/template';

    public function process(ContainerBuilder $container): void
    {
        $directories = $this->resolveDirectories($container);
        if ([] === $directories) {
            return;
        }

        $errors = [];

        foreach ((new Finder())->in($directories)->name('*.xml') as $file) {
            $container->addResource(new FileResource($file->getPathname()));
            $this->collectErrorsFromFile($file->getPathname(), $errors);
        }

        if ([] !== $errors) {
            throw new \RuntimeException(
                "Outdated selection param names found in template and form XML files:\n\n"
                . \implode("\n", $errors)
                . "\n\nPlease update the params to their replacements."
            );
        }
    }

    /**
     * @return list<string>
     */
    private function resolveDirectories(ContainerBuilder $container): array
    {
        $directories = [];

        if ($container->hasParameter('sulu_admin.templates.configuration')) {
            /** @var array<string, array{default_type: string|null, directories: array<string>}> $configuration */
            $configuration = $container->getParameter('sulu_admin.templates.configuration');

            foreach ($configuration as $config) {
                foreach ($config['directories'] as $directory) {
                    $directories[] = $directory;
                }
            }
        }

        if ($container->hasParameter('sulu_admin.forms.directories')) {
            /** @var array<string> $formDirectories */
            $formDirectories = $container->getParameter('sulu_admin.forms.directories');

            foreach ($formDirectories as $directory) {
                $directories[] = $directory;
            }
        }

        $resolved = [];
        foreach ($directories as $directory) {
            $realPath = \realpath($directory);
            if (false === $realPath) {
                continue;
            }

            $resolved[$realPath] = true;
        }

        return \array_keys($resolved);
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

        $keyNodes = $xpath->query('/t:template/t:key | /t:form/t:key');
        $keyNode = false !== $keyNodes ? $keyNodes->item(0) : null;
        $key = null !== $keyNode ? $keyNode->nodeValue ?? '' : '';

        foreach (self::OUTDATED_PARAMS as $fieldType => $replacements) {
            $this->collectErrorsForFieldType(
                $xpath,
                $fieldType,
                $replacements,
                $key,
                $filePath,
                $errors,
            );
        }
    }

    /**
     * @param array<string, string> $replacements
     * @param list<string> $errors
     */
    private function collectErrorsForFieldType(
        \DOMXPath $xpath,
        string $fieldType,
        array $replacements,
        string $key,
        string $filePath,
        array &$errors,
    ): void {
        $properties = $xpath->query(\sprintf('//t:property[@type="%s"]', $fieldType)) ?: [];

        foreach ($properties as $property) {
            \assert($property instanceof \DOMElement);
            $propertyName = $property->getAttribute('name');

            foreach ($xpath->query('t:params/t:param', $property) ?: [] as $param) {
                \assert($param instanceof \DOMElement);
                $name = $param->getAttribute('name');

                if (!isset($replacements[$name])) {
                    continue;
                }

                $errors[] = \sprintf(
                    self::OUTDATED_MESSAGE,
                    $key,
                    $propertyName,
                    $name,
                    $replacements[$name],
                    $filePath,
                );
            }
        }
    }
}
