<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\ResourceLocator\PathCleanup;

use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class PathCleanup implements PathCleanupInterface
{
    /**
     * @param array<string, array<string, string>> $replacers
     */
    public function __construct(
        private SluggerInterface $slugger,
        private array $replacers = [
            'default' => PathCleanupInterface::DEFAULT_REPLACERS,
            'de' => PathCleanupInterface::DE_REPLACERS,
            'en' => PathCleanupInterface::EN_REPLACERS,
            'fr' => PathCleanupInterface::FR_REPLACERS,
            'it' => PathCleanupInterface::IT_REPLACERS,
            'nl' => PathCleanupInterface::NL_REPLACERS,
            'es' => PathCleanupInterface::ES_REPLACERS,
            'bg' => PathCleanupInterface::BG_REPLACERS,
        ],
    ) {
    }

    public function cleanup(string $path, string $locale): string
    {
        $replacers = $this->replacers['default'] ?? [];

        $replacers = \array_merge(
            $replacers,
            $this->replacers[$locale] ?? []
        );
        $locale = \str_replace('-', '_', $locale);

        if (\count($replacers) > 0) {
            foreach ($replacers as $key => $value) {
                $path = \str_replace($key, $value, $path);
            }
        }

        // replace multiple dash with one
        $path = \preg_replace('/([-]+)/', '-', $path);

        // remove dash before slash
        $path = \preg_replace('/[-]+\//', '/', $path); // @phpstan-ignore-line argument.type

        // remove dash after slash
        $path = \preg_replace('/\/[-]+/', '/', $path); // @phpstan-ignore-line argument.type

        // delete dash at the beginning or end
        $path = \preg_replace('/^([-])/', '', $path); // @phpstan-ignore-line argument.type
        $path = \preg_replace('/([-])$/', '', $path); // @phpstan-ignore-line argument.type

        // replace multiple slashes
        $path = \preg_replace('/([\/]+)/', '/', $path); // @phpstan-ignore-line argument.type

        $parts = \explode('/', $path); // @phpstan-ignore-line argument.type
        $newParts = [];

        $totalParts = \count($parts);
        foreach ($parts as $i => $part) {
            $slug = $this->slugger->slug($part, '-', $locale);
            $slug = $slug->lower();
            if (0 === $i || $i + 1 === $totalParts || !$slug->isEmpty()) {
                $newParts[] = $slug->toString();
            }
        }

        return \implode('/', $newParts);
    }
}
