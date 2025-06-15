<?php

namespace Sulu\Bundle\AdminBundle\SmartContent;

use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DatasourceItemInterface;

interface SmartContentProviderInterface
{
    public function getConfiguration(): ProviderConfigurationInterface;

    /**
     * @param array<string, mixed> $filters
     */
    public function countBy(array $filters): int;

    /**
     * @param array<string, mixed> $filters
     * @param array<string, string> $sortBys
     * @return array<array{id: string, title: string}>
     */
    public function findFlatBy(array $filters, array $sortBys): array;

    public function getType(): string;

    // TODO adjust ResourceLoaders to use ResourceKeys to get rid of this method and use the `getType` resource as the default resource loader key.
    public function getResourceLoaderKey(): string;

    /**
     * @param mixed $datasource
     * @param mixed[] $propertyParameter
     * @param mixed[] $parameters
     *
     * @return DatasourceItemInterface|null
     */
    public function resolveDatasource(mixed $datasource, array $propertyParameter, array $parameters): ?DatasourceItemInterface;
}
