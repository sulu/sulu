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
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-type Entry array{key: string, template: string, title: array<string, string>, cache-invalidation: mixed}
 */
class SnippetAreaCompilerPass implements CompilerPassInterface
{
    use XmlParserTrait;

    public const SNIPPET_AREA_PARAM = 'sulu_snippet.areas';

    private TranslatorInterface $translator;

    /** @var array<string> */
    private array $locales;

    public function __construct(
        private string $configDirectory
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        $this->locales = $container->getParameter('sulu_core.locales');
        //$this->translator = $container->get('translator');

        try {
            $files = (new Finder())
                ->in($this->configDirectory)
                ->files()
                ->name('*.xml')
            ;
        } catch (DirectoryNotFoundException) {
            // If the directory does not exist we don't have any snippet areas.
            $container->setParameter(self::SNIPPET_AREA_PARAM, []);

            return;
        }

        $keyLocations = [];

        $areas = [];
        foreach ($this->getAreaIterator($files) as $filePath => $areaXml) {
            $areaKey = $areaXml->attributes->getNamedItem('key')->textContent;

            // We don't allow duplicate definitions of area keys
            if (\array_key_exists($areaKey, $keyLocations)) {
                throw new \InvalidArgumentException(\sprintf(
                    'Snippet area "%s" need to be unique it is defined in "%s" and "%s"',
                    $areaKey,
                    $keyLocations[$areaKey],
                    $filePath,
                ));
            }

            $areas[$areaKey] = [
                'title' => $this->getTitles($areaXml->getElementsByTagName('title')),
                'cache-invalidation' => 'true' === $areaXml->attributes->getNamedItem('cache-invalidation')?->value,
            ];

            $keyLocations[$areaKey] = $filePath;
        }

        \ksort($areas);

        $container->setParameter(self::SNIPPET_AREA_PARAM, $areas);
    }

    /**
     * @param \DOMNodeList<DOMNode|DOMNameSpaceNode> $templateTitles
     *
     * @return array<string, string>
     */
    private function getTitles(\DOMNodeList $templateTitles): array
    {
        $titles = [];

        // If we only have one title and no locale (indexed 0) then it's a translation key
        if (1 === $templateTitles->length && 0 === $templateTitles->item(0)->attributes->length) {
            //$translator = $container->get('translator');
            $titleToTranslate = $templateTitles->item(0)->textContent;
            foreach ($this->locales as $locale) {
                $titleToTranslate = \trim($titleToTranslate);
                // $titles[$locale] = $this->translator->trans($titleToTranslate, [], 'admin', $locale);
                $titles[$locale] = $titleToTranslate;
            }
        } else {
            foreach ($templateTitles->getIterator() as $title) {
                $titles[$title->attributes->getNamedItem('lang')->textContent] = \trim($title->textContent);
            }
        }

        return $titles;
    }

    /**
     * Returns a map of file path to area dom element.
     *
     * @return \Generator<string, DOMElement>
     */
    private function getAreaIterator(Finder $files): \Generator
    {
        foreach ($files as $file) {
            $xml = new \DOMDocument();
            $xml->resolveExternals = false;
            $xml->substituteEntities = false;
            $xml->loadXML($file->getContents(), \LIBXML_NONET);

            $element = [];
            foreach ($xml->getElementsByTagName('area') as $areaXml) {
                yield $file->getPath => $areaXml;
            }
        }
    }
}
