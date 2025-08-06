<?php

namespace Sulu\Content\Tests\Application\ExampleTestBundle\PropertyResolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\ResourceLoader\ExampleResourceLoader;

class ExampleSelectionPropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_array($data)
            || 0 === \count($data)
            || !\array_is_list($data)
        ) {
            return ContentView::create([], ['ids' => [], ...$params]);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? ExampleResourceLoader::getKey();

        /** @var string[] $ids */
        $ids = $data;

        return ContentView::createResolvables(
            ids: $ids,
            resourceLoaderKey: $resourceLoaderKey,
            view: [
                'ids' => $ids,
                ...$params,
            ],
            priority: 150
        );
    }

    public static function getType(): string
    {
        return 'example_selection';
    }
}
