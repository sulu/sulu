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

        $metaData = [];
        foreach ($files as $file) {
            $xml = new \DOMDocument();
            $xml->resolveExternals = false;
            $xml->substituteEntities = false;
            $xml->loadXML($file->getContents(), \LIBXML_NONET);

            $element = [];
            foreach ($xml->getElementsByTagName('area') as $areaXml) {
                $element[] = [
                    'key' => $areaXml->attributes->getNamedItem('key')->textContent,
                    'title' => $this->getTitles($this->locales, $areaXml->getElementsByTagName('title')),
                    'cache-invalidation' => (bool) $areaXml->attributes->getNamedItem('cache-invalidation')?->value,
                ];
            }
            $key = $xml->getElementsByTagName('key')->item(0)->textContent;

            $metaData[$key] = $element;
        }

        \ksort($metaData);

        $container->setParameter(self::SNIPPET_AREA_PARAM, $metaData);
    }

    /**
     * @param array<string> $locales
     *
     * @return array<string, string>
     */
    private function getTitles(array $locales, \DOMNodeList $templateTitles): array
    {
        $titles = [];

        // If we only have one title and no locale (indexed 0) then it's a translation key
        if (1 === $templateTitles->length && 0 === $templateTitles->item(0)->attributes->length) {
            //$translator = $container->get('translator');
            $titleToTranslate = $templateTitles->item(0)->textContent;
            foreach ($locales as $locale) {
                // $titles[$locale] = $this->translator->trans($titleToTranslate, [], 'admin', $locale);
                $titles[$locale] = \trim($titleToTranslate);
            }
        } else {
            foreach ($templateTitles->getIterator() as $title) {
                $titles[$title->attributes->getNamedItem('lang')->textContent] = $title->textContent;
            }
        }

        return $titles;
    }
}
