<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ContentResolver\Resolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

readonly class DimensionContentResolver implements ResolverInterface
{
    public function __construct(private PropertyAccessorInterface $propertyAccessor)
    {
    }

    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView
    {
        if ([] === $properties) {
            return null;
        }

        $properties = $this->filterProperties(
            \array_merge($this->getDefaultProperties(), $properties ?? [])
        );

        $data = [];
        foreach ($properties as $key => $path) {
            if ($this->propertyAccessor->isReadable($dimensionContent, $path)) {
                $data[$key] = $this->propertyAccessor->getValue($dimensionContent, $path);
            }
        }

        return ContentView::create(
            $data,
            []
        );
    }

    /**
     * @return array<string, string>
     */
    private function getDefaultProperties(): array
    {
        return [
            'id' => self::getPrefix() . 'id',
        ];
    }

    /**
     * @param array<string, string> $properties
     *
     * @return array<string, string>
     */
    private function filterProperties(array $properties): array
    {
        $filteredProperties = [];
        foreach ($properties as $key => $value) {
            if (\str_starts_with($value, self::getPrefix())) {
                $filteredProperties[$key] = \str_replace(self::getPrefix(), '', $value);
            }
        }

        return $filteredProperties;
    }

    public static function getPrefix(): string
    {
        return 'object.';
    }
}
