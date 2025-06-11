<?php

namespace Sulu\Bundle\AdminBundle\SmartContent;

use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DatasourceItemInterface;

interface SmartContentProviderInterface
{
    public function getConfiguration(): ProviderConfigurationInterface;

    public function countBy(array $filters, array $sortBys): int;

    public function findFlatBy(array $filters, array $sortBys): array;

    public function getType(): string;

    // TODO adjust ResourceLoaders to use ResourceKeys to get rid of this method and use the `getType` resource as the default resource loader key.
    public function getResourceLoaderKey(): string;

    public function resolveDatasource($datasource, array $propertyParameter, array $parameters): ?DatasourceItemInterface;
}
