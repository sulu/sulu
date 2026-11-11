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

namespace Sulu\Bundle\MediaBundle\Media\Classification;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Selectable classifications of a media, configured in "sulu_media.classifications".
 *
 * @internal
 */
class ClassificationProvider
{
    /**
     * @param array<string, string> $classifications translation key of the title by classification key
     */
    public function __construct(
        private array $classifications,
        private TranslatorInterface $translator,
    ) {
    }

    public function hasClassifications(): bool
    {
        return [] !== $this->classifications;
    }

    /**
     * @return array<array{name: string, title: string}>
     */
    public function getValues(string $locale): array
    {
        $values = [];
        foreach ($this->classifications as $name => $title) {
            $values[] = ['name' => $name, 'title' => $this->translator->trans($title, [], 'admin', $locale)];
        }

        return $values;
    }
}
