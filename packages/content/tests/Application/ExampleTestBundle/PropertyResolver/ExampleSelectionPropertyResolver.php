<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Application\ExampleTestBundle\PropertyResolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WebspaceInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\ResourceLoader\ExampleResourceLoader;

class ExampleSelectionPropertyResolver implements PropertyResolverInterface
{
    /**
     * @param array{
     *     resourceLoader?: string,
     *     properties?: array<string, mixed>|null,
     * } $params
     */
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

        return ContentView::createResolvablesWithReferences(
            ids: $ids,
            resourceLoaderKey: $resourceLoaderKey,
            resourceKey: Example::RESOURCE_KEY,
            view: [],
            priority: 150,
            metadata: [
                'properties' => $params['properties'] ?? null,
            ],
            viewCallback: static function(mixed $source): array {
                $view = [];

                if ($source instanceof TemplateInterface) {
                    $view['template'] = $source->getTemplateKey();
                }

                if ($source instanceof WebspaceInterface) {
                    $view['mainWebspace'] = $source->getMainWebspace();
                }

                if ($source instanceof AuthorInterface) {
                    $view['authored'] = $source->getAuthored()?->format('c');
                    $view['lastModified'] = $source->getLastModified()?->format('c');
                }

                return $view;
            },
        );
    }

    public static function getType(): string
    {
        return 'example_selection';
    }
}
