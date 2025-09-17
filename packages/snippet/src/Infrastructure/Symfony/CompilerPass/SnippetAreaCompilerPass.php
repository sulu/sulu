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

use Dom\NodeList;
use Dom\XMLDocument;
use Sulu\Bundle\AdminBundle\Metadata\XmlParserTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

        $metaData = [];
        foreach (new Finder()->in($this->configDirectory)->files()->name('*.xml') as $file) {
            $xml = XMLDocument::createFromFile($file);

            $element = [];
            foreach ($xml->querySelectorAll('area') as $areaXml) {
                $element = [
                    'key' => $areaXml->attributes->getNamedItem('key')->textContent,
                    'template' => $areaXml->querySelector('template')?->textContent,
                    'title' => $this->getTitles($this->locales, $areaXml->querySelectorAll('meta title')),
                    'cache-invalidation' => $areaXml->querySelector('cache-invalidation'),
                ];
            }
            $key = $xml->querySelector('key')->textContent;

            $metaData[$key] = $element;
        }

        \ksort($areas);

        $container->setParameter(self::SNIPPET_AREA_PARAM, $areas);
    }

    /**
     * @param array<string> $locales
     *
     * @return array<string, string>
     */
    private function getTitles(array $locales, NodeList $templateTitles): array
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
