<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Tests\Unit\Infrastructure\Symfony\HttpKernel\Compiler;

use PHPUnit\Framework\TestCase;
use Sulu\Article\Infrastructure\Symfony\HttpKernel\Compiler\ValidateDefaultMainWebspacePass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ValidateDefaultMainWebspacePassTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            $this->removeTempDir($dir);
        }
        $this->tempDirs = [];
    }

    public function testThrowsWhenMultipleWebspacesAndNoDefaultConfigured(): void
    {
        $dir = $this->createWebspaceDir(['sulu-io', 'blog']);
        $container = $this->createContainer($dir, []);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('sulu_article.default_main_webspace');

        (new ValidateDefaultMainWebspacePass())->process($container);
    }

    public function testDoesNotThrowWhenDefaultMainWebspaceConfigured(): void
    {
        $dir = $this->createWebspaceDir(['sulu-io', 'blog']);
        $container = $this->createContainer($dir, ['default' => 'sulu-io']);

        (new ValidateDefaultMainWebspacePass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testDoesNotThrowWhenLocaleSpecificMainWebspaceConfigured(): void
    {
        $dir = $this->createWebspaceDir(['sulu-io', 'blog']);
        $container = $this->createContainer($dir, ['en' => 'sulu-io', 'de' => 'blog']);

        (new ValidateDefaultMainWebspacePass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testDoesNotThrowWhenSingleWebspace(): void
    {
        $dir = $this->createWebspaceDir(['sulu-io']);
        $container = $this->createContainer($dir, []);

        (new ValidateDefaultMainWebspacePass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testThrowsWhenConfiguredMainWebspaceDoesNotExist(): void
    {
        $dir = $this->createWebspaceDir(['sulu-io', 'blog']);
        $container = $this->createContainer($dir, ['default' => 'nonexistent']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('nonexistent');

        (new ValidateDefaultMainWebspacePass())->process($container);
    }

    public function testThrowsWhenLocaleSpecificConfiguredWebspaceDoesNotExist(): void
    {
        $dir = $this->createWebspaceDir(['sulu-io', 'blog']);
        $container = $this->createContainer($dir, ['en' => 'sulu-io', 'de' => 'nope']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('nope');

        (new ValidateDefaultMainWebspacePass())->process($container);
    }

    public function testThrowsWhenConfiguredWebspaceDoesNotExistWithSingleWebspace(): void
    {
        $dir = $this->createWebspaceDir(['sulu-io']);
        $container = $this->createContainer($dir, ['default' => 'nonexistent']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('nonexistent');

        (new ValidateDefaultMainWebspacePass())->process($container);
    }

    public function testRegistersConfigDirAsContainerResource(): void
    {
        $dir = $this->createWebspaceDir(['sulu-io']);
        $container = $this->createContainer($dir, []);

        (new ValidateDefaultMainWebspacePass())->process($container);

        $directoryResources = \array_filter(
            $container->getResources(),
            static fn ($resource): bool => $resource instanceof DirectoryResource,
        );
        $resourcePaths = \array_map(
            static fn (DirectoryResource $resource): string => $resource->getResource(),
            $directoryResources,
        );

        $this->assertContains(\realpath($dir), $resourcePaths);
    }

    public function testDoesNotThrowWhenConfigDirParameterMissing(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sulu_article.default_main_webspace', []);

        (new ValidateDefaultMainWebspacePass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testDoesNotThrowWhenConfigDirDoesNotExist(): void
    {
        $container = $this->createContainer('/nonexistent/path/that/does/not/exist', []);

        (new ValidateDefaultMainWebspacePass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testResolvesProjectDirPlaceholderInConfigDir(): void
    {
        $projectDir = $this->createTempDir();
        $webspacesDir = $projectDir . '/config/webspaces';
        \mkdir($webspacesDir, 0777, true);
        \file_put_contents($webspacesDir . '/sulu.io.xml', $this->webspaceXml('sulu-io'));
        \file_put_contents($webspacesDir . '/blog.xml', $this->webspaceXml('blog'));

        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $projectDir);
        $container->setParameter('sulu_core.webspace.config_dir', '%kernel.project_dir%/config/webspaces');
        $container->setParameter('sulu_article.default_main_webspace', []);

        $this->expectException(InvalidConfigurationException::class);

        (new ValidateDefaultMainWebspacePass())->process($container);
    }

    /**
     * @param array<string, string> $defaultMainWebspace
     */
    private function createContainer(string $configDir, array $defaultMainWebspace): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('sulu_core.webspace.config_dir', $configDir);
        $container->setParameter('sulu_article.default_main_webspace', $defaultMainWebspace);

        return $container;
    }

    /**
     * @param list<string> $keys
     */
    private function createWebspaceDir(array $keys): string
    {
        $dir = $this->createTempDir();
        foreach ($keys as $key) {
            \file_put_contents($dir . '/' . $key . '.xml', $this->webspaceXml($key));
        }

        return $dir;
    }

    private function webspaceXml(string $key): string
    {
        return <<<XML
            <?xml version="1.0" encoding="utf-8"?>
            <webspace xmlns="http://schemas.sulu.io/webspace/webspace">
                <name>{$key}</name>
                <key>{$key}</key>
            </webspace>
            XML;
    }

    private function createTempDir(): string
    {
        $dir = \sys_get_temp_dir() . '/sulu_test_' . \uniqid('', true);
        \mkdir($dir, 0777, true);
        $this->tempDirs[] = $dir;

        return $dir;
    }

    private function removeTempDir(string $dir): void
    {
        if (!\is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                \rmdir((string) $file->getRealPath());
            } else {
                \unlink((string) $file->getRealPath());
            }
        }

        \rmdir($dir);
    }
}
