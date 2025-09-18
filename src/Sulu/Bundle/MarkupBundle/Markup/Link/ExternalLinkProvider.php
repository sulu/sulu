<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup\Link;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class ExternalLinkProvider implements LinkProviderInterface
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function getConfiguration()
    {
        /** @var LinkConfigurationBuilder $linkConfigurationBuilder */
        $linkConfigurationBuilder = LinkConfigurationBuilder::create();

        return $linkConfigurationBuilder
            ->setTitle($this->translator->trans('sulu_admin.external_link', [], 'admin'))
            ->setResourceKey('')
            ->setListAdapter('')
            ->setDisplayProperties([])
            ->setOverlayTitle('')
            ->setEmptyText('')
            ->setIcon('');
    }

    public function preload(array $hrefs, $locale, $published = true)
    {
        if (0 === \count($hrefs)) {
            return [];
        }

        $result = [];
        foreach ($hrefs as $href) {
            $result[] = new LinkItem($href, '', $href, true);
        }

        return $result;
    }
}
