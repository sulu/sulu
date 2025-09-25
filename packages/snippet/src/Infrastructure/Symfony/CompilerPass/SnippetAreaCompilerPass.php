<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Infrastructure\Symfony\CompilerPass;

use Sulu\Bundle\AdminBundle\Metadata\XmlParserTrait;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-type AreaData array{key: string, titles: array<string, string>, cacheInvalidation: bool}
 * @phpstan-type TemplateData array{templateKey: string, filePath: string, areas: array<AreaData>}
 * @phpstan-type SnippetAreaConfig array<string, array{title: array<string, string>, cache-invalidation: bool, areaKey: string, template: string}>
 */
class SnippetAreaCompilerPass implements CompilerPassInterface
{
    use XmlParserTrait;

    public const SNIPPET_AREA_PARAM = 'sulu_snippet.areas';

    /** @var array<string> */
    private array $locales;

    private TranslatorInterface $translator;

    public function process(ContainerBuilder $container): void
    {
        $this->locales = $container->getParameter('sulu_core.locales');
        $this->translator = $container->get('translator');

        $snippetTemplateDirectories = $this->getSnippetTemplateDirectories($container);
        $areas = $this->loadAreasFromXmlFiles($snippetTemplateDirectories);

        \ksort($areas);
        $container->setParameter(self::SNIPPET_AREA_PARAM, $areas);
    }

    /**
     * @param array<string> $templateDirectories
     *
     * @return array<string, array{title: array<string, string>, cache-invalidation: bool, areaKey: string, template: string}>
     */
    private function loadAreasFromXmlFiles(array $templateDirectories): array
    {
        if (empty($templateDirectories)) {
            return [];
        }

        $areas = [];
        $keyLocations = [];

        foreach ($templateDirectories as $templateDirectory) {
            try {
                $files = $this->findXmlFiles($templateDirectory);
            } catch (DirectoryNotFoundException) {
                continue; // Skip directories that don't exist
            }

            foreach ($this->getTemplateDataIterator($files) as $templateData) {
                $areas = \array_merge($areas, $this->processTemplateAreas($templateData, $keyLocations));
            }
        }

        return $areas;
    }

    /**
     * @return array<string>
     */
    private function getSnippetTemplateDirectories(ContainerBuilder $container): array
    {
        // Try to get directories from sulu_admin.templates.configuration parameter
        if (!$container->hasParameter('sulu_admin.templates.configuration')) {
            throw new \RuntimeException('The "sulu_admin.templates.configuration" parameter is not available. Make sure the SuluAdminBundle is properly configured.');
        }

        /** @var array<string, mixed> $templatesConfig */
        $templatesConfig = $container->getParameter('sulu_admin.templates.configuration');

        if (!isset($templatesConfig[SnippetInterface::TEMPLATE_TYPE]) || !\is_array($templatesConfig[SnippetInterface::TEMPLATE_TYPE]) || !isset($templatesConfig[SnippetInterface::TEMPLATE_TYPE]['directories']) || !\is_array($templatesConfig[SnippetInterface::TEMPLATE_TYPE]['directories'])) {
            throw new \RuntimeException(\sprintf(
                'No template directories configured for snippet template type "%s" in sulu_admin.templates.configuration parameter.',
                SnippetInterface::TEMPLATE_TYPE
            ));
        }

        /** @var array<string, string> $directories */
        $directories = (array) $templatesConfig[SnippetInterface::TEMPLATE_TYPE]['directories'];

        return $this->resolveDirectoryPaths($directories, $container);
    }

    /**
     * @param array<string, string> $directories
     *
     * @return array<string>
     */
    private function resolveDirectoryPaths(array $directories, ContainerBuilder $container): array
    {
        $resolvedPaths = [];

        foreach ($directories as $directory) {
            // Resolve parameter placeholders like %kernel.project_dir%
            $resolvedPath = $container->resolveEnvPlaceholders($directory, true);
            if (\is_string($resolvedPath)) {
                $resolvedPaths[] = $resolvedPath;
            }
        }

        return $resolvedPaths;
    }

    private function findXmlFiles(string $directory): Finder
    {
        return (new Finder())
            ->in($directory)
            ->files()
            ->name('*.xml');
    }

    /**
     * @param TemplateData $templateData
     * @param array<string, string> $keyLocations
     *
     * @return array<string, array{title: array<string, string>, cache-invalidation: bool, areaKey: string, template: string}>
     */
    private function processTemplateAreas(array $templateData, array &$keyLocations): array
    {
        $areas = [];

        foreach ($templateData['areas'] as $areaData) {
            $areaKey = $areaData['key'];
            $filePath = $templateData['filePath'];

            $this->validateUniqueAreaKey($areaKey, $keyLocations, $filePath);

            $areas[$areaKey] = [
                'title' => $areaData['titles'],
                'cache-invalidation' => $areaData['cacheInvalidation'],
                'areaKey' => $areaKey,
                'template' => $templateData['templateKey'],
            ];

            $keyLocations[$areaKey] = $filePath;
        }

        return $areas;
    }

    /**
     * @param array<string, string> $keyLocations
     */
    private function validateUniqueAreaKey(string $areaKey, array $keyLocations, string $currentFilePath): void
    {
        if (\array_key_exists($areaKey, $keyLocations)) {
            throw new \InvalidArgumentException(\sprintf(
                'Snippet area "%s" must be unique. It is defined in both "%s" and "%s"',
                $areaKey,
                $keyLocations[$areaKey],
                $currentFilePath,
            ));
        }
    }

    /**
     * @return \Generator<TemplateData>
     */
    private function getTemplateDataIterator(Finder $files): \Generator
    {
        foreach ($files as $file) {
            $templateData = $this->parseTemplateFile($file);

            if (null !== $templateData) {
                yield $templateData;
            }
        }
    }

    /**
     * @return TemplateData|null
     */
    private function parseTemplateFile(\SplFileInfo $file): ?array
    {
        $xml = $this->loadXmlDocument(\file_get_contents($file->getPathname()) ?: '');
        $templateKey = $this->extractTemplateKey($xml);

        $areas = $this->extractAreas($xml);

        if (empty($areas)) {
            return null;
        }

        return [
            'templateKey' => $templateKey,
            'filePath' => $file->getPathname(),
            'areas' => $areas,
        ];
    }

    private function loadXmlDocument(string $content): \DOMDocument
    {
        $xml = new \DOMDocument();
        $xml->resolveExternals = false;
        $xml->substituteEntities = false;

        if (!$xml->loadXML($content, \LIBXML_NONET)) {
            throw new \RuntimeException('Failed to load XML document');
        }

        return $xml;
    }

    private function extractTemplateKey(\DOMDocument $xml): string
    {
        $keyElements = $xml->getElementsByTagName('key');

        if (0 === $keyElements->length) {
            throw new \RuntimeException('Template key is missing in XML document');
        }

        $firstElement = $keyElements->item(0);
        if (null === $firstElement) {
            throw new \RuntimeException('Template key element is missing in XML document');
        }

        return \trim($firstElement->textContent ?: '');
    }

    /**
     * @return array<AreaData>
     */
    private function extractAreas(\DOMDocument $xml): array
    {
        $areas = [];
        $areaElements = $xml->getElementsByTagName('area');

        foreach ($areaElements as $areaElement) {
            $areaData = $this->parseAreaElement($areaElement);

            if (null !== $areaData) {
                $areas[] = $areaData;
            }
        }

        return $areas;
    }

    /**
     * @return AreaData|null
     */
    private function parseAreaElement(\DOMElement $areaElement): ?array
    {
        $keyAttribute = $areaElement->getAttribute('key');

        if (empty($keyAttribute)) {
            return null;
        }

        $cacheInvalidation = match ($areaElement->getAttribute('cache-invalidation')) {
            'false' => false,
            default => true,
        };
        $titles = $this->extractAreaTitles($areaElement);

        return [
            'key' => $keyAttribute,
            'titles' => $titles,
            'cacheInvalidation' => $cacheInvalidation,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function extractAreaTitles(\DOMElement $areaElement): array
    {
        $titleElements = $areaElement->getElementsByTagName('title');

        if (0 === $titleElements->length) {
            return $this->translateTitle('');
        }

        return $this->processTitleElements($titleElements);
    }

    /**
     * @param \DOMNodeList<\DOMElement> $titleElements
     *
     * @return array<string, string>
     */
    private function processTitleElements(\DOMNodeList $titleElements): array
    {
        $titles = [];

        foreach ($titleElements as $titleElement) {
            $locale = $titleElement->getAttribute('lang');
            $titleText = \trim($titleElement->textContent);

            if (empty($locale)) {
                // No lang attribute - translate for all locales
                return $this->translateTitle($titleText);
            }

            // Has lang attribute - use as given
            if (!empty($titleText)) {
                $titles[$locale] = $titleText;
            }
        }

        return $titles;
    }

    /**
     * @return array<string, string>
     */
    private function translateTitle(string $title): array
    {
        $titles = [];

        foreach ($this->locales as $locale) {
            $titles[$locale] = $this->translator->trans($title, [], 'admin', $locale);
        }

        return $titles;
    }
}
