<?php

declare(strict_types=1);

namespace Sulu\Content\Application\SmartResolver\Resolver;

use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SmartContentSmartResolver implements SmartResolverInterface
{
    /**
     * @param ServiceLocator<SmartContentProviderInterface> $smartContentProviders
     */
    public function __construct(
        private ServiceLocator $smartContentProviders,
    ) {
    }

    public function resolve(SmartResolvable $resolvable, ?string $locale = null): ContentView
    {
        /** @var array{
         *     value?: array<string, mixed>,
         *     filters?: array<string, mixed>,
         *     sortBys?: array<string, string>,
         *     parameters?: array<string, mixed>,
         * } $data
         */
        $data = $resolvable->getData();

        $filters = $data['filters'] ?? [];
        $sortBys = $data['sortBys'] ?? [];
        $parameters = $data['parameters'] ?? [];

        /** @var int|null $limit */
        $limit = $filters['limitResult'] ?? null;
        /** @var int $page */
        $page = $filters['page'] ?? 1;

        $provider = $parameters['provider'] ?? null;

        if (!\is_string($provider)) {
            throw new \InvalidArgumentException(\sprintf('The "provider" must be a string, %s given.', \gettype($provider)));
        }

        if (!$this->smartContentProviders->has($provider)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'No smart content provider found for key "%s". Existing keys: %s',
                    $provider,
                    \implode(', ', \array_keys($this->smartContentProviders->getProvidedServices())),
                ),
            );
        }
        $smartContentProvider = $this->smartContentProviders->get($provider);

        $params = ['value' => $data['value'] ?? null, ...$parameters];
        $result = $smartContentProvider->findFlatBy($filters, $sortBys, $params);
        $total = ($limit && \count($result) < $limit) ? \count($result) : $smartContentProvider->countBy($filters, $params);

        // TODO verify filters
        $view = [
            ...$filters,
            ...$sortBys,
            'provider' => $provider,
            'page' => $page,
            'hasNextPage' => null !== $limit && ($total > ($limit * $page)),
            'paginated' => null !== $limit,
            'total' => $total,
            'maxPage' => (null !== $limit) ? (int) \ceil($total / $limit) : null,
            'limit' => $limit,
            // categoryRoot
            // categoriesParameter
            // tagsParameter
            // excluded
            // category
            // websiteTags
            // websiteTagsOperator
            // websiteCategories
            // websiteCategoriesOperator
        ];

        return ContentView::createResolvables(
            ids: \array_map(static fn (array $item) => $item['id'], $result),
            resourceLoaderKey: $smartContentProvider->getResourceLoaderKey(),
            view: $view,
        );
    }

    public static function getType(): string
    {
        return 'smart_content';
    }
}
