<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser;

use Sulu\Bundle\AdminBundle\Metadata\XmlParserTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal this class is not part of the public API and may be changed or removed without further notice
 */
class MetaXmlParser
{
    use XmlParserTrait;

    /**
     * @var string[]
     */
    private $locales;

    /**
     * @param array<string, string> $locales
     */
    public function __construct(
        private TranslatorInterface $translator,
        array $locales,
    ) {
        $this->locales = \array_keys($locales);
    }

    /**
     * @return array{
     *     title?: array<string, string>,
     *     info_text?: array<string, string>,
     *     placeholder?: array<string, string>,
     * }
     */
    public function load(\DOMXPath $xpath, \DOMNode $context): array
    {
        $result = [];
        $metaNode = ($xpath->query('x:meta', $context) ?: null)?->item(0);

        if (!$metaNode) {
            return $result;
        }

        $result['title'] = $this->loadMetaTag('x:title', $xpath, $metaNode);
        $result['info_text'] = $this->loadMetaTag('x:info_text', $xpath, $metaNode);
        $result['placeholder'] = $this->loadMetaTag('x:placeholder', $xpath, $metaNode);

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function loadMetaTag(string $path, \DOMXPath $xpath, ?\DOMNode $context = null): array
    {
        $result = [];

        $translationKey = null;

        foreach (($xpath->query($path, $context) ?: []) as $node) {
            $lang = (string) $this->getValueFromXPath('@lang', $xpath, $node);

            if (!$lang) {
                $translationKey = $node->textContent;

                continue;
            }

            $result[$lang] = $node->textContent;
        }

        if (!$translationKey) {
            return $result;
        }

        $missingLocales = \array_diff($this->locales, \array_keys($result));
        foreach ($missingLocales as $missingLocale) {
            $result[$missingLocale] = $this->translator->trans($translationKey, [], 'admin', $missingLocale);
        }

        return $result;
    }
}
