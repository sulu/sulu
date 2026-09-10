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

namespace Sulu\Content\Tests\Unit\Infrastructure\Symfony\HttpKernel\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\RequestWorkflow\PreValidator\Builtin\ExcerptRequiredPreValidator;
use Sulu\Content\Application\RequestWorkflow\PreValidator\Builtin\SeoRequiredPreValidator;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\ValidateRequestWorkflowsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(ValidateRequestWorkflowsPass::class)]
class ValidateRequestWorkflowsPassTest extends TestCase
{
    private string $templateDirectory;

    protected function setUp(): void
    {
        $directory = \sys_get_temp_dir() . '/sulu-request-workflow-pass-' . \uniqid();
        (new Filesystem())->mkdir($directory);

        // the pass reports the resolved path, which differs from the temp path on macOS
        $this->templateDirectory = (string) \realpath($directory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->templateDirectory);
    }

    public function testConfiguredKeysAndTemplateTagsPass(): void
    {
        $container = $this->createContainer(['seo' => ['pre_validators' => ['seo_required' => null]]]);
        $this->registerPreValidator($container, 'sulu_content.seo_required', SeoRequiredPreValidator::class);
        $this->writeTemplate('seo.xml', 'seo');

        (new ValidateRequestWorkflowsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testTemplateTagNamingAnUnconfiguredWorkflowIsReported(): void
    {
        $container = $this->createContainer(['seo' => []]);
        $this->writeTemplate('ghost.xml', 'ghost');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('names the workflow "ghost", which is not configured. Configured: seo (file: ' . $this->templateDirectory . '/ghost.xml)');

        (new ValidateRequestWorkflowsPass())->process($container);
    }

    public function testTemplateTagOptingOutOfReviewIsAccepted(): void
    {
        $container = $this->createContainer(['seo' => []]);
        $this->writeTemplate('none.xml', 'none');

        (new ValidateRequestWorkflowsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testPreValidatorKeyWithoutAServiceIsReported(): void
    {
        $container = $this->createContainer(['seo' => ['pre_validators' => ['does_not_exist' => null]]]);
        $this->registerPreValidator($container, 'sulu_content.seo_required', SeoRequiredPreValidator::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Workflow "seo" references the pre_validator "does_not_exist", which no service registers. Registered: seo_required');

        (new ValidateRequestWorkflowsPass())->process($container);
    }

    public function testTwoServicesSharingAKeyAreReported(): void
    {
        $container = $this->createContainer([]);
        $this->registerPreValidator($container, 'sulu_content.seo_required', SeoRequiredPreValidator::class);
        $this->registerPreValidator($container, 'app.own_seo_check', ExcerptRequiredPreValidator::class, 'seo_required');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The pre_validator key "seo_required" is registered by several services: sulu_content.seo_required, app.own_seo_check');

        (new ValidateRequestWorkflowsPass())->process($container);
    }

    /**
     * A service that is autoconfigured AND explicitly tagged keeps both tags, and the locator builds
     * one entry per tag occurrence, so it also registers under its own getKey() and would silently
     * replace the built-in holding that key.
     */
    public function testADoublyTaggedServiceShadowingABuiltInIsReported(): void
    {
        $container = $this->createContainer([]);
        $this->registerPreValidator($container, 'sulu_content.seo_required', SeoRequiredPreValidator::class);

        $definition = new Definition(SeoRequiredPreValidator::class);
        $definition->addTag('sulu_content.request_workflow_pre_validator', ['key' => 'own_seo_check']);
        $definition->addTag('sulu_content.request_workflow_pre_validator');
        $container->setDefinition('app.own_seo_check', $definition);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The pre_validator key "seo_required" is registered by several services: sulu_content.seo_required, app.own_seo_check');

        (new ValidateRequestWorkflowsPass())->process($container);
    }

    public function testAnAutoconfiguredServiceOverridingItsOwnKeyIsNotADuplicate(): void
    {
        $container = $this->createContainer(['seo' => ['pre_validators' => ['own_seo_check' => null]]]);

        // the shape autoconfiguration leaves behind: the tag it added, the project's own tag with the
        // overriding key, and an abstract copy of the definition carrying both
        $definition = new Definition(SeoRequiredPreValidator::class);
        $definition->addTag('sulu_content.request_workflow_pre_validator', ['key' => 'own_seo_check']);
        $definition->addTag('sulu_content.request_workflow_pre_validator');
        $container->setDefinition('app.own_seo_check', $definition);

        $abstract = clone $definition;
        $abstract->setAbstract(true);
        $container->setDefinition('.abstract.instanceof.app.own_seo_check', $abstract);

        (new ValidateRequestWorkflowsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    public function testWebsiteContainerIsNotChecked(): void
    {
        $container = $this->createContainer(['seo' => ['pre_validators' => ['does_not_exist' => null]]]);
        $container->setParameter('sulu.context', 'website');

        (new ValidateRequestWorkflowsPass())->process($container);

        $this->addToAssertionCount(1);
    }

    /**
     * @param array<string, array{pre_validators?: array<string, mixed>, validators?: array<string, mixed>}> $workflows
     */
    private function createContainer(array $workflows): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('sulu.context', 'admin');
        $container->setParameter('sulu_content.request_workflows', $workflows);
        $container->setParameter('sulu_admin.forms.directories', [$this->templateDirectory]);

        return $container;
    }

    /**
     * @param class-string $class
     */
    private function registerPreValidator(ContainerBuilder $container, string $id, string $class, ?string $key = null): void
    {
        $definition = new Definition($class);
        $definition->addTag('sulu_content.request_workflow_pre_validator', null !== $key ? ['key' => $key] : []);

        $container->setDefinition($id, $definition);
    }

    private function writeTemplate(string $fileName, string $workflowName): void
    {
        \file_put_contents($this->templateDirectory . '/' . $fileName, <<<XML
            <?xml version="1.0" ?>
            <template xmlns="http://schemas.sulu.io/template/template">
                <key>a-template</key>

                <tag name="sulu_content.request_workflow" workflow="{$workflowName}"/>
            </template>
            XML);
    }
}
