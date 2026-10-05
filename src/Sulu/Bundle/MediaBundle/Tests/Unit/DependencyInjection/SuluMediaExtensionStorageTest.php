<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\DependencyInjection;

use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\SuluMediaExtension;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemVersion;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Loads the storage services without the adapters installed, so the wiring of flysystem 1.x and 3.x is checked in
 * every environment.
 */
class SuluMediaExtensionStorageTest extends TestCase
{
    /**
     * @param array<string, mixed> $storageConfig
     */
    #[DataProvider('provideStorages')]
    public function testConfigureStorage(string $storage, array $storageConfig, mixed $expectedPathPrefix): void
    {
        $container = $this->configureStorage($storage, $storageConfig);

        $this->assertSame($expectedPathPrefix, $container->getParameter('sulu_media.media.storage.' . $storage . '.path_prefix'));
    }

    public function testS3Adapter(): void
    {
        $container = $this->configureStorage('s3', ['path_prefix' => null]);

        $this->assertSame(
            FlysystemVersion::isV3() ? AwsS3V3Adapter::class : AwsS3Adapter::class,
            $container->getDefinition('sulu_media.storage.s3.adapter')->getClass()
        );
        $this->assertCount(4, $container->getDefinition('sulu_media.storage.s3')->getArguments());
    }

    public function testGoogleCloudAdapter(): void
    {
        $container = $this->configureStorage('google_cloud', ['path_prefix' => null]);

        $adapter = $container->getDefinition('sulu_media.storage.google_cloud.adapter');
        $this->assertSame(
            FlysystemVersion::isV3() ? GoogleCloudStorageAdapter::class : GoogleStorageAdapter::class,
            $adapter->getClass()
        );
        $this->assertCount(FlysystemVersion::isV3() ? 2 : 3, $adapter->getArguments());
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, mixed}>
     */
    public static function provideStorages(): iterable
    {
        $emptyPrefix = FlysystemVersion::isV3() ? '' : null;

        yield 's3 without prefix' => ['s3', ['path_prefix' => null], $emptyPrefix];
        yield 's3 with prefix' => ['s3', ['path_prefix' => 'media'], 'media'];
        yield 'google_cloud without prefix' => ['google_cloud', ['path_prefix' => null], $emptyPrefix];
        yield 'azure_blob without prefix' => ['azure_blob', ['path_prefix' => null], $emptyPrefix];
        yield 'azure_blob with prefix' => ['azure_blob', ['path_prefix' => 'media'], 'media'];
    }

    /**
     * @param array<string, mixed> $storageConfig
     */
    private function configureStorage(string $storage, array $storageConfig): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../Resources/config'));

        $method = new \ReflectionMethod(SuluMediaExtension::class, 'configureStorage');
        $method->invoke(new SuluMediaExtension(), ['storage' => $storage, 'storages' => [$storage => $storageConfig]], $container, $loader);

        return $container;
    }
}
