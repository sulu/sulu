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

namespace Sulu\Bundle\AdminBundle\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\ValidateSmartContentParamsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ValidateSmartContentParamsPassTest extends TestCase
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

    public function testNoTemplatesConfigurationParameter(): void
    {
        $container = new ContainerBuilder();
        (new ValidateSmartContentParamsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testNonExistentDirectoriesAreSkipped(): void
    {
        $container = $this->createContainer(['/nonexistent/path/that/does/not/exist']);

        (new ValidateSmartContentParamsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testValidSmartContentParamsPass(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'valid.xml', 'valid-template', 'my_property', 'articles', [
            'groups' => 'blog,news',
            'templateKeys' => 'default',
        ]);

        $container = $this->createContainer([$dir]);
        (new ValidateSmartContentParamsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testDeprecatedTypesParamThrowsForArticles(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_property', 'articles', [
            'types' => 'blog',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is deprecated, rename to "groups"');

        (new ValidateSmartContentParamsPass())->process($container);
    }

    public function testDeprecatedTypesParamThrowsForArticlesPageTree(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_property', 'articles_page_tree', [
            'types' => 'blog',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is deprecated, rename to "groups"');

        (new ValidateSmartContentParamsPass())->process($container);
    }

    public function testDeprecatedTypesParamThrowsForPageProvider(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_property', 'pages', [
            'types' => 'blog',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/param "types" is deprecated, rename to "templateKeys"/');

        (new ValidateSmartContentParamsPass())->process($container);
    }

    public function testDeprecatedStructureTypesParamThrowsForArticles(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_property', 'articles', [
            'structureTypes' => 'default',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "structureTypes" is deprecated, rename to "templateKeys"');

        (new ValidateSmartContentParamsPass())->process($container);
    }

    public function testSmartContentWithNoProviderUsesTemplateReplacement(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_property', null, [
            'types' => 'blog',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/param "types" is deprecated, rename to "templateKeys"/');

        (new ValidateSmartContentParamsPass())->process($container);
    }

    public function testSmartContentInSectionIsValidated(): void
    {
        $dir = $this->createTempTemplateDir();
        $xml = $this->templateWrapper(
            'bad-template',
            <<<'XML'
        <section name="content">
            <properties>
                <property name="my_property" type="smart_content">
                    <meta><title lang="en">Smart Content</title></meta>
                    <params>
                        <param name="provider" value="articles"/>
                        <param name="types" value="blog"/>
                    </params>
                </property>
            </properties>
        </section>
XML,
        );
        \file_put_contents($dir . '/bad.xml', $xml);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is deprecated, rename to "groups"');

        (new ValidateSmartContentParamsPass())->process($container);
    }

    public function testSmartContentInBlockTypeIsValidated(): void
    {
        $dir = $this->createTempTemplateDir();
        $xml = $this->templateWrapper(
            'bad-template',
            <<<'XML'
        <block name="content_block" default-type="default">
            <types>
                <type name="default">
                    <properties>
                        <property name="smart_articles" type="smart_content">
                            <params>
                                <param name="provider" value="articles"/>
                                <param name="types" value="blog"/>
                            </params>
                        </property>
                    </properties>
                </type>
            </types>
        </block>
XML,
        );
        \file_put_contents($dir . '/bad.xml', $xml);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is deprecated, rename to "groups"');

        (new ValidateSmartContentParamsPass())->process($container);
    }

    public function testNonSmartContentPropertiesAreIgnored(): void
    {
        $dir = $this->createTempTemplateDir();
        $xml = $this->templateWrapper(
            'ok-template',
            <<<'XML'
        <property name="title" type="text_line">
            <params>
                <param name="types" value="something"/>
            </params>
        </property>
XML,
        );
        \file_put_contents($dir . '/ok.xml', $xml);

        $container = $this->createContainer([$dir]);

        (new ValidateSmartContentParamsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testMultipleErrorsAreCollected(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_property', 'articles', [
            'types' => 'blog',
            'structureTypes' => 'default',
        ]);

        $container = $this->createContainer([$dir]);

        try {
            (new ValidateSmartContentParamsPass())->process($container);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('param "types" is deprecated, rename to "groups"', $message);
            $this->assertStringContainsString('param "structureTypes" is deprecated, rename to "templateKeys"', $message);
        }
    }

    /**
     * @param list<string> $directories
     */
    private function createContainer(array $directories): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('sulu_admin.templates.configuration', [
            'pages' => [
                'default_type' => null,
                'directories' => $directories,
            ],
        ]);

        return $container;
    }

    private function createTempTemplateDir(): string
    {
        $dir = \sys_get_temp_dir() . '/sulu_test_' . \uniqid('', true);
        \mkdir($dir, 0777, true);
        $this->tempDirs[] = $dir;

        return $dir;
    }

    /**
     * @param array<string, string> $params
     */
    private function writeTemplateXml(
        string $dir,
        string $filename,
        string $templateKey,
        string $propertyName,
        ?string $provider,
        array $params,
    ): void {
        $paramLines = [];
        if (null !== $provider) {
            $paramLines[] = \sprintf('                <param name="provider" value="%s"/>', $provider);
        }
        foreach ($params as $name => $value) {
            $paramLines[] = \sprintf('                <param name="%s" value="%s"/>', $name, $value);
        }
        $paramsXml = [] !== $paramLines
            ? "<params>\n" . \implode("\n", $paramLines) . "\n            </params>"
            : '';

        $content = <<<XML
        <property name="{$propertyName}" type="smart_content">
            <meta><title lang="en">Smart Content</title></meta>
            {$paramsXml}
        </property>
XML;

        \file_put_contents($dir . '/' . $filename, $this->templateWrapper($templateKey, $content));
    }

    private function templateWrapper(string $templateKey, string $propertiesContent): string
    {
        return <<<XML
<?xml version="1.0" ?>
<template xmlns="http://schemas.sulu.io/template/template"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/template-1.0.xsd">
    <key>{$templateKey}</key>
    <view>test</view>
    <controller>TestController::indexAction</controller>
    <cacheLifetime>0</cacheLifetime>
    <meta>
        <title lang="en">Test</title>
    </meta>
    <properties>
{$propertiesContent}
    </properties>
</template>
XML;
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
