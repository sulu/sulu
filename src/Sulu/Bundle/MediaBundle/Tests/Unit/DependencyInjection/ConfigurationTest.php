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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testStorageIsEmptyByDefault(): void
    {
        $this->assertEquals(
            ['name' => null, 'flysystem_service' => null, 'segments' => null],
            $this->process([])['storage']
        );
    }

    public function testStorageName(): void
    {
        $this->assertEquals(
            ['name' => 's3', 'flysystem_service' => null, 'segments' => null],
            $this->process(['storage' => 's3'])['storage']
        );
    }

    public function testFlysystemStorage(): void
    {
        $this->assertEquals(
            ['name' => null, 'flysystem_service' => 'media.storage', 'segments' => 20],
            $this->process(['storage' => ['flysystem_service' => 'media.storage', 'segments' => 20]])['storage']
        );
    }

    public function testFlysystemStorageIsMergedOverConfigurations(): void
    {
        $storage = $this->process(
            ['storage' => ['flysystem_service' => 'media.storage']],
            ['storage' => ['segments' => 20]],
        )['storage'];

        $this->assertEquals(['name' => null, 'flysystem_service' => 'media.storage', 'segments' => 20], $storage);
    }

    public function testStorageNameAndFlysystemStorageCanNotBeCombined(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['storage' => 's3'], ['storage' => ['segments' => 20]]);
    }

    /**
     * @param array<string, mixed>|int $storage
     */
    #[DataProvider('provideInvalidStorages')]
    public function testInvalidStorage(array|int $storage): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['storage' => $storage]);
    }

    /**
     * @return iterable<string, array{array<string, mixed>|int}>
     */
    public static function provideInvalidStorages(): iterable
    {
        yield 'unknown key' => [['path' => '/var/uploads']];
        yield 'service is no string' => [['flysystem_service' => ['media']]];
        yield 'segments is no integer' => [['segments' => ['10']]];
        yield 'segments is no number' => [['segments' => 'many']];
        yield 'no array or name' => [10];
    }

    /**
     * @param array<string, mixed> ...$configs
     *
     * @return array<mixed>
     */
    private function process(array ...$configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }
}
