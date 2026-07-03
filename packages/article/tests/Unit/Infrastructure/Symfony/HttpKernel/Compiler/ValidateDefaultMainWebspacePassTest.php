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

    public function testThrowsWhenDefaultWebspaceDoesNotSupportLocale(): void
    {
        $dir = $this->createWebspaceDirWithLocales([
            'website' => ['en', 'de'],
            'magazine' => ['en'],
        ]);
        $container = $this->createContainer($dir, ['default' => 'magazine']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('locale "de"');

        (new ValidateDefaultMainWebspacePass())->process($container);
    }

    public function testThrowsWhenLocaleSpecificWebspaceDoesNotSupportItsLocale(): void
    {
        $dir = $this->createWebspaceDirWithLocales([
            'website' => ['en', 'de'],
            'magazine' => ['en'],
        ]);
        $container = $this->createContainer($dir, ['en' => 'website', 'de' => 'magazine']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('maps locale "de" to webspace "magazine"');

        (new ValidateDefaultMainWebspacePass())->process($container);
    }

    public function testThrowsWhenAuthorableLocaleHasNoMapping(): void
    {
        // "de" is authorable but has neither a per-locale nor a "default" mapping.
        $dir = $this->createWebspaceDirWithLocales([
            'website' => ['en'],
            'magazine' => ['de'],
        ]);
        $container = $this->createContainer($dir, ['en' => 'website']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('locale "de" has no');

        (new ValidateDefaultMainWebspacePass())->process($container);
    }

    public function testDoesNotThrowWhenPerLocaleMappingsCoverDisjointLocales(): void
    {
        $dir = $this->createWebspaceDirWithLocales([
            'website' => ['en'],
            'magazine' => ['de'],
        ]);
        $container = $this->createContainer($dir, ['en' => 'website', 'de' => 'magazine']);

        (new ValidateDefaultMainWebspacePass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testDoesNotThrowWhenDefaultCoversLocalesNotOverriddenPerLocale(): void
    {
        $dir = $this->createWebspaceDirWithLocales([
            'website' => ['en', 'de'],
            'magazine' => ['fr'],
        ]);
        $container = $this->createContainer($dir, ['default' => 'website', 'fr' => 'magazine']);

        (new ValidateDefaultMainWebspacePass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testValidatesCountryLocales(): void
    {
        // Mixed granularity: language_country locales alongside bare-language ones.
        $dir = $this->createWebspaceDirWithLocales([
            'bwt_country' => ['de_de', 'de_at'],
            'bwt_pharma' => ['de', 'en'],
        ]);
        $container = $this->createContainer($dir, [
            'de_de' => 'bwt_country',
            'de_at' => 'bwt_country',
            'de' => 'bwt_pharma',
            'en' => 'bwt_pharma',
        ]);

        (new ValidateDefaultMainWebspacePass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testThrowsWhenDefaultDoesNotSupportCountryLocale(): void
    {
        // "default" => bwt_country covers de_de/de_at but not the bare "de" from bwt_pharma.
        $dir = $this->createWebspaceDirWithLocales([
            'bwt_country' => ['de_de', 'de_at'],
            'bwt_pharma' => ['de'],
        ]);
        $container = $this->createContainer($dir, ['default' => 'bwt_country']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('locale "de"');

        (new ValidateDefaultMainWebspacePass())->process($container);
    }

    public function testNormalizesCountryLocaleCaseToMatchRuntime(): void
    {
        // Uppercase ISO country codes in the XML must resolve like Localization::getLocale(),
        // which lowercases them, so a lowercase per-locale config entry still matches.
        $dir = $this->createWebspaceDirWithLocales([
            'website' => ['de_AT'],
            'magazine' => ['en'],
        ]);
        $container = $this->createContainer($dir, ['de_at' => 'website', 'en' => 'magazine']);

        (new ValidateDefaultMainWebspacePass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testIgnoresPortalLocalizationsWhenCollectingWebspaceLocales(): void
    {
        // A <portal> may declare its own <localizations>, but runtime Webspace::getLocalization()
        // only considers the webspace-level <localizations>. The pass must mirror that and not
        // treat a portal-only locale ("de") as supported by the webspace.
        $dir = $this->createTempDir();
        \file_put_contents($dir . '/website.xml', $this->webspaceXmlWithPortalLocales('website', ['en'], ['de']));
        \file_put_contents($dir . '/magazine.xml', $this->webspaceXml('magazine', ['de']));

        $container = $this->createContainer($dir, ['default' => 'website']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('maps locale "de" to webspace "website"');

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

    /**
     * @param array<string, list<string>> $webspaces webspace key => supported locales
     */
    private function createWebspaceDirWithLocales(array $webspaces): string
    {
        $dir = $this->createTempDir();
        foreach ($webspaces as $key => $locales) {
            \file_put_contents($dir . '/' . $key . '.xml', $this->webspaceXml($key, $locales));
        }

        return $dir;
    }

    /**
     * @param list<string> $locales
     */
    private function webspaceXml(string $key, array $locales = ['en', 'de']): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>' . "\n"
            . '<webspace xmlns="http://schemas.sulu.io/webspace/webspace">' . "\n"
            . '    <name>' . $key . '</name>' . "\n"
            . '    <key>' . $key . '</key>' . "\n"
            . '    <localizations>' . "\n"
            . $this->renderLocalizations($locales)
            . '    </localizations>' . "\n"
            . '</webspace>' . "\n";
    }

    /**
     * @param list<string> $webspaceLocales
     * @param list<string> $portalLocales
     */
    private function webspaceXmlWithPortalLocales(string $key, array $webspaceLocales, array $portalLocales): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>' . "\n"
            . '<webspace xmlns="http://schemas.sulu.io/webspace/webspace">' . "\n"
            . '    <name>' . $key . '</name>' . "\n"
            . '    <key>' . $key . '</key>' . "\n"
            . '    <localizations>' . "\n"
            . $this->renderLocalizations($webspaceLocales)
            . '    </localizations>' . "\n"
            . '    <portals>' . "\n"
            . '        <portal>' . "\n"
            . '            <localizations>' . "\n"
            . $this->renderLocalizations($portalLocales)
            . '            </localizations>' . "\n"
            . '        </portal>' . "\n"
            . '    </portals>' . "\n"
            . '</webspace>' . "\n";
    }

    /**
     * @param list<string> $locales
     */
    private function renderLocalizations(array $locales): string
    {
        $localizations = '';
        foreach ($locales as $locale) {
            $parts = \explode('_', $locale, 2);
            $country = isset($parts[1]) ? \sprintf(' country="%s"', $parts[1]) : '';
            $localizations .= \sprintf('        <localization language="%s"%s/>', $parts[0], $country) . "\n";
        }

        return $localizations;
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
