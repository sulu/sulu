<?php

declare(strict_types=1);

namespace Sulu\Content\Application\PropertyResolver\Resolver;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Symfony\Component\HttpFoundation\RequestStack;

class SmartContentPropertyResolver implements PropertyResolverInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * @param array{
     *     categories?: int[],
     *     tags?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagOperator?: 'AND'|'OR',
     *     sortBy?: string,
     *     sortDirection?: 'ASC'|'DESC',
     *     limitResult?: int|null,
     * } $data
     * @param array<string, mixed> $params
     */
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_array($data)) { // @phpstan-ignore function.alreadyNarrowedType
            return ContentView::create($data, $params);
        }

        $metadata = $params['metadata'] ?? null;
        if (!$metadata instanceof FieldMetadata) {
            throw new \InvalidArgumentException('The "metadata" parameter must be an instance of FieldMetadata.');
        }

        $parameters = $this->getOptions($metadata);
        // Default parameters
        /**
         * @var array{
         *     locale: string|null,
         *     page_parameter: string,
         *     tags_parameter: string,
         *     categories_parameter: string,
         *     website_tags_operator: 'AND'|'OR',
         *     website_categories_operator: 'AND'|'OR',
         *     exclude_duplicates: bool,
         *     provider: string,
         *     } $parameters
         */
        $parameters = \array_merge([
            'locale' => $locale,
            'page_parameter' => 'p',
            'tags_parameter' => 'tags',
            'categories_parameter' => 'categories',
            'website_tags_operator' => 'OR',
            'website_categories_operator' => 'OR',
            'exclude_duplicates' => false,
        ], $parameters);
        $this->validateParameters($parameters);

        $request = $this->requestStack->getCurrentRequest();
        \assert(null !== $request, 'Request must not be null');

        /** @var array{
         *     locale?: string|null,
         *     categoryIds?: int[],
         *     categoryOperator?: 'AND'|'OR',
         *     tagIds?: int[],
         *     tagNames?: string[],
         *     tagOperator?: 'AND'|'OR',
         *     limit?: int,
         *     page?: int,
         *     webspaceKey?: string|null,
         * } $filters
         */
        $filters = [
            'locale' => $parameters['locale'],
            'categoryIds' => \array_merge(
                $data['categories'] ?? [],
                \array_filter(
                    \explode(
                        ',',
                        $request->query->getString($parameters['categories_parameter']),
                    ),
                ),
            ),
            'categoryOperator' => \strtoupper($data['categoryOperator'] ?? $parameters['website_categories_operator']),
            'tagNames' => \array_merge($data['tags'] ?? [], \array_filter(\explode(',', $request->query->getString($parameters['tags_parameter'])))),
            'tagOperator' => \strtoupper($data['tagOperator'] ?? $parameters['website_tags_operator']),
            'limit' => $data['limitResult'] ?? null,
            'page' => $request->query->getInt($parameters['page_parameter'], 1),
            // TODO exclude_duplicates
        ];

        $sortBys = $data['sortBy'] ?? null ? [$data['sortBy'] = $data['sortDirection'] ?? 'ASC'] : null;

        $data = [
            ...$data,
            'filters' => $filters,
            'sortBys' => $sortBys,
            'provider' => $parameters['provider'],
            'parameters' => $parameters,
        ];

        return ContentView::createSmartResolvable(
            data: $data,
            resourceLoaderKey: 'smart_content',
            view: $data,
        );
    }

    public static function getType(): string
    {
        return 'smart_content';
    }

    /**
     * @return array<string|int, string|int|mixed[]|bool|null>
     */
    private function getOptions(FieldMetadata $metadata): array
    {
        $parameters = [];
        foreach ($metadata->getOptions() as $option) {
            $parameters[$option->getName()] = $this->getOption($option);
        }

        return $parameters;
    }

    /**
     * @return array<string|int, mixed>|string|int|bool|null
     */
    private function getOption(OptionMetadata $metadata): string|int|array|bool|null
    {
        if (OptionMetadata::TYPE_COLLECTION === $metadata->getType()) {
            $values = [];
            $metadataValues = $metadata->getValue();
            if (!\is_array($metadataValues)) {
                throw new \InvalidArgumentException(
                    \sprintf('The value of option "%s" from type %s, must be an array, %s given.', $metadata->getName(), $metadata->getType(), \gettype($metadataValues)),
                );
            }
            foreach ($metadataValues as $option) {
                $values[$option->getName()] = $this->getOption($option);
            }

            return $values;
        }

        /** @var string|int|bool|null $result */
        $result = $metadata->getValue() ?? $metadata->getName();

        return $result;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function validateParameters(array $parameters): void
    {
        if (!isset($parameters['provider'])) {
            throw new \InvalidArgumentException('The "provider" parameter is required.');
        }

        if (!\is_string($parameters['provider'])) {
            throw new \InvalidArgumentException('The "provider" parameter must be a string.');
        }

        foreach (['website_tags_operator', 'website_categories_operator'] as $operator) {
            if ($parameters[$operator] ?? null) {
                /** @var string $operatorValue */
                $operatorValue = $parameters[$operator];
                $parameters[$operator] = \strtoupper($operatorValue);

                if (!\in_array($parameters[$operator], ['AND', 'OR'], true)) {
                    throw new \InvalidArgumentException(
                        \sprintf('The "%s" option must be either "AND" or "OR".', $operator),
                    );
                }
            }
        }
    }
}
