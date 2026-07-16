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
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\ValidateSelectionParamsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ValidateSelectionParamsPassTest extends TestCase
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
        (new ValidateSelectionParamsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testNonExistentDirectoriesAreSkipped(): void
    {
        $container = $this->createContainer(['/nonexistent/path/that/does/not/exist']);

        (new ValidateSelectionParamsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testValidSelectionParamsPass(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'valid.xml', 'valid-template', 'my_snippets', 'snippet_selection', [
            'templateKeys' => 'footer,header',
        ]);

        $container = $this->createContainer([$dir]);

        (new ValidateSelectionParamsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testTypesParamThrowsForSnippetSelection(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_snippets', 'snippet_selection', [
            'types' => 'footer',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is not supported, use "templateKeys"');

        (new ValidateSelectionParamsPass())->process($container);
    }

    public function testTypesParamThrowsForSingleSnippetSelection(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_snippet', 'single_snippet_selection', [
            'types' => 'footer',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is not supported, use "templateKeys"');

        (new ValidateSelectionParamsPass())->process($container);
    }

    public function testTypesParamThrowsForArticleSelection(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_articles', 'article_selection', [
            'types' => 'blog',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is not supported, use "groups"');

        (new ValidateSelectionParamsPass())->process($container);
    }

    public function testTemplatesParamThrowsForArticleSelection(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_articles', 'article_selection', [
            'templates' => 'default',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "templates" is not supported, use "templateKeys"');

        (new ValidateSelectionParamsPass())->process($container);
    }

    public function testTemplatesParamThrowsForSingleArticleSelection(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_article', 'single_article_selection', [
            'templates' => 'default',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "templates" is not supported, use "templateKeys"');

        (new ValidateSelectionParamsPass())->process($container);
    }

    public function testTypesParamThrowsForPageSelection(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_pages', 'page_selection', [
            'types' => 'default',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is not supported, use "templateKeys"');

        (new ValidateSelectionParamsPass())->process($container);
    }

    public function testTypesParamThrowsForSinglePageSelection(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_page', 'single_page_selection', [
            'types' => 'default',
        ]);

        $container = $this->createContainer([$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is not supported, use "templateKeys"');

        (new ValidateSelectionParamsPass())->process($container);
    }

    public function testTemplateKeysParamOnPageSelectionIsValid(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'ok.xml', 'ok-template', 'my_pages', 'page_selection', [
            'templateKeys' => 'default',
        ]);

        $container = $this->createContainer([$dir]);

        (new ValidateSelectionParamsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testTypesParamOnMediaSelectionIsIgnored(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'ok.xml', 'ok-template', 'my_media', 'media_selection', [
            'types' => 'image,video',
        ]);

        $container = $this->createContainer([$dir]);

        (new ValidateSelectionParamsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testTypesParamOnContactSelectionIsIgnored(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'ok.xml', 'ok-template', 'my_contacts', 'contact_selection', [
            'types' => 'something',
        ]);

        $container = $this->createContainer([$dir]);

        (new ValidateSelectionParamsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testNonSelectionPropertiesAreIgnored(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'ok.xml', 'ok-template', 'title', 'text_line', [
            'types' => 'something',
        ]);

        $container = $this->createContainer([$dir]);

        (new ValidateSelectionParamsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testSelectionInBlockTypeIsValidated(): void
    {
        $dir = $this->createTempTemplateDir();
        $xml = $this->templateWrapper(
            'bad-template',
            <<<'XML'
        <block name="content_block" default-type="default">
            <types>
                <type name="default">
                    <properties>
                        <property name="my_snippets" type="snippet_selection">
                            <params>
                                <param name="types" value="footer"/>
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
        $this->expectExceptionMessage('param "types" is not supported, use "templateKeys"');

        (new ValidateSelectionParamsPass())->process($container);
    }

    public function testErrorMessageContainsKeyPropertyAndFile(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'my-template', 'my_snippets', 'snippet_selection', [
            'types' => 'footer',
        ]);

        $container = $this->createContainer([$dir]);

        try {
            (new ValidateSelectionParamsPass())->process($container);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('Key "my-template"', $message);
            $this->assertStringContainsString('property "my_snippets"', $message);
            $this->assertStringContainsString($dir . '/bad.xml', $message);
        }
    }

    public function testMultipleErrorsAreCollected(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_articles', 'article_selection', [
            'types' => 'blog',
            'templates' => 'default',
        ]);

        $container = $this->createContainer([$dir]);

        try {
            (new ValidateSelectionParamsPass())->process($container);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('param "types" is not supported, use "groups"', $message);
            $this->assertStringContainsString('param "templates" is not supported, use "templateKeys"', $message);
        }
    }

    public function testSelectionInFormXmlIsValidated(): void
    {
        $dir = $this->createTempTemplateDir();
        \file_put_contents(
            $dir . '/bad.xml',
            $this->formWrapper('my-form', $this->propertyXml('my_snippets', 'snippet_selection', [
                'types' => 'footer',
            ])),
        );

        $container = $this->createContainerWithForms([$dir]);

        try {
            (new ValidateSelectionParamsPass())->process($container);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('param "types" is not supported, use "templateKeys"', $message);
            $this->assertStringContainsString('Key "my-form"', $message);
        }
    }

    public function testFormsAreScannedWithoutTemplatesConfiguration(): void
    {
        $dir = $this->createTempTemplateDir();
        \file_put_contents(
            $dir . '/bad.xml',
            $this->formWrapper('my-form', $this->propertyXml('my_snippets', 'snippet_selection', [
                'types' => 'footer',
            ])),
        );

        $container = new ContainerBuilder();
        $container->setParameter('sulu_admin.forms.directories', [$dir]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is not supported, use "templateKeys"');

        (new ValidateSelectionParamsPass())->process($container);
    }

    public function testDirectoryInBothConfigurationsIsScannedOnce(): void
    {
        $dir = $this->createTempTemplateDir();
        $this->writeTemplateXml($dir, 'bad.xml', 'bad-template', 'my_snippets', 'snippet_selection', [
            'types' => 'footer',
        ]);

        $container = $this->createContainer([$dir]);
        $container->setParameter('sulu_admin.forms.directories', [$dir]);

        try {
            (new ValidateSelectionParamsPass())->process($container);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame(
                1,
                \substr_count($e->getMessage(), 'param "types" is not supported, use "templateKeys"'),
                'The same file must not be reported twice when a directory is configured as both a template and a form directory.',
            );
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

    /**
     * @param list<string> $directories
     */
    private function createContainerWithForms(array $directories): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('sulu_admin.forms.directories', $directories);

        return $container;
    }

    private function formWrapper(string $formKey, string $propertiesContent): string
    {
        return <<<XML
<?xml version="1.0" ?>
<form xmlns="http://schemas.sulu.io/template/template"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/form-1.0.xsd">
    <key>{$formKey}</key>

    <properties>
{$propertiesContent}
    </properties>
</form>
XML;
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
        string $fieldType,
        array $params,
    ): void {
        \file_put_contents(
            $dir . '/' . $filename,
            $this->templateWrapper($templateKey, $this->propertyXml($propertyName, $fieldType, $params)),
        );
    }

    /**
     * @param array<string, string> $params
     */
    private function propertyXml(string $propertyName, string $fieldType, array $params): string
    {
        $paramLines = [];
        foreach ($params as $name => $value) {
            $paramLines[] = \sprintf('                <param name="%s" value="%s"/>', $name, $value);
        }
        $paramsXml = [] !== $paramLines
            ? "<params>\n" . \implode("\n", $paramLines) . "\n            </params>"
            : '';

        return <<<XML
        <property name="{$propertyName}" type="{$fieldType}">
            <meta><title lang="en">Selection</title></meta>
            {$paramsXml}
        </property>
XML;
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
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            \assert($file instanceof \SplFileInfo);
            if ($file->isDir()) {
                \rmdir($file->getPathname());

                continue;
            }

            \unlink($file->getPathname());
        }

        \rmdir($dir);
    }
}
