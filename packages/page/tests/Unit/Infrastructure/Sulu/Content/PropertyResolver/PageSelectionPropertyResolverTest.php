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

namespace Sulu\Bundle\Page\Tests\Unit\Infrastructure\Sulu\Content\PropertyResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Security as WebspaceSecurity;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\PageSelectionPropertyResolver;
use Symfony\Bundle\SecurityBundle\Security;

#[CoversClass(PageSelectionPropertyResolver::class)]
class PageSelectionPropertyResolverTest extends TestCase
{
    use ProphecyTrait;

    private PageSelectionPropertyResolver $resolver;
    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private ObjectProphecy $requestAnalyzer;

    public function setUp(): void
    {
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->requestAnalyzer->getWebspace()->willReturn(null);

        $this->resolver = new PageSelectionPropertyResolver(
            null,
            null,
            $this->requestAnalyzer->reveal()
        );
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve([], 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['ids' => []], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve([], 'en', ['custom' => 'params']);

        $this->assertSame([], $contentView->getContent());
        $this->assertSame([
            'ids' => [],
            'custom' => 'params',
        ], $contentView->getView());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['ids' => []], $contentView->getView());
    }

    /**
     * @return iterable<array{
     *     0: mixed,
     * }>
     */
    public static function provideUnresolvableData(): iterable
    {
        yield 'null' => [null];
        yield 'smart_content' => [['source' => '123']];
        yield 'single_value' => [1];
        yield 'object' => [(object) [1, 2]];
    }

    /**
     * @param array<string|int> $data
     */
    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(array $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        foreach ($data as $key => $value) {
            $resolvable = $content[$key] ?? null;
            $this->assertInstanceOf(ResolvableResource::class, $resolvable);
            $this->assertSame($value, $resolvable->getId());
            $this->assertSame('page', $resolvable->getResourceLoaderKey());
        }

        $references = $contentView->getReferences();
        $this->assertCount(\count($data), $references);
        foreach ($data as $key => $value) {
            $reference = $references[$key] ?? null;
            $this->assertNotNull($reference);
            $this->assertSame($value, $reference->getResourceId());
            $this->assertSame(PageInterface::RESOURCE_KEY, $reference->getResourceKey());
        }

        $this->assertSame(['ids' => $data], $contentView->getView());
    }

    /**
     * @return iterable<array{
     *     0: array<string|int>,
     * }>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'empty' => [[]];
        yield 'string_list' => [['1', '2']];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve([1], 'en', ['resourceLoader' => 'custom_Page']);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $resolvable = $content[0] ?? null;
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame(1, $resolvable->getId());
        $this->assertSame('custom_Page', $resolvable->getResourceLoaderKey());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame(1, $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }

    public function testResolveWithMetadata(): void
    {
        $contentView = $this->resolver->resolve(['1'], 'en', [
            'properties' => [
                'property1' => 'value1',
                'property2' => 'value2',
            ],
        ]);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);

        $this->assertSame([
            'properties' => [
                'property1' => 'value1',
                'property2' => 'value2',
            ],
            'filters' => [
                'locale' => 'en',
                'stage' => 'live',
            ],
        ], $content[0]->getMetadata());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame('1', $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }

    public function testResolveWithoutMetadata(): void
    {
        $contentView = $this->resolver->resolve(['1'], 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);

        $this->assertSame([
            'properties' => null,
            'filters' => [
                'locale' => 'en',
                'stage' => 'live',
            ],
        ], $content[0]->getMetadata());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame('1', $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }

    public function testResolveWithEmptyMetadata(): void
    {
        $metadata = new FieldMetadata('test_field');

        $contentView = $this->resolver->resolve(['1'], 'en', ['metadata' => $metadata]);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);

        $this->assertSame([
            'properties' => null,
            'filters' => [
                'locale' => 'en',
                'stage' => 'live',
            ],
        ], $content[0]->getMetadata());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame('1', $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }

    public function testResolveWithSecureWebspaceAndAuthenticatedUser(): void
    {
        $webspace = new Webspace();
        $webspaceSecurity = new WebspaceSecurity();
        $webspaceSecurity->setSystem('website');
        $webspaceSecurity->setPermissionCheck(true);
        $webspace->setSecurity($webspaceSecurity);

        $user = $this->prophesize(UserInterface::class);

        $security = $this->prophesize(Security::class);
        $security->getUser()->willReturn($user->reveal());

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $resolver = new PageSelectionPropertyResolver(
            $security->reveal(),
            [PermissionTypes::VIEW => 64],
            $requestAnalyzer->reveal()
        );

        $contentView = $resolver->resolve(['1'], 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);

        $this->assertSame([
            'properties' => null,
            'filters' => [
                'locale' => 'en',
                'stage' => 'live',
                'permissionConfig' => [
                    'user' => $user->reveal(),
                    'permission' => 64,
                ],
            ],
        ], $content[0]->getMetadata());
    }

    public function testResolveWithNonSecureWebspace(): void
    {
        $webspace = new Webspace();

        $user = $this->prophesize(UserInterface::class);

        $security = $this->prophesize(Security::class);
        $security->getUser()->willReturn($user->reveal());

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $resolver = new PageSelectionPropertyResolver(
            $security->reveal(),
            [PermissionTypes::VIEW => 64],
            $requestAnalyzer->reveal()
        );

        $contentView = $resolver->resolve(['1'], 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);

        $metadata = $content[0]->getMetadata();
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('filters', $metadata);
        $filters = $metadata['filters'];
        $this->assertIsArray($filters);
        $this->assertArrayNotHasKey('permissionConfig', $filters);
    }

    public function testResolvePermissionConfigPreservesOtherMetadata(): void
    {
        $webspace = new Webspace();
        $webspaceSecurity = new WebspaceSecurity();
        $webspaceSecurity->setSystem('website');
        $webspaceSecurity->setPermissionCheck(true);
        $webspace->setSecurity($webspaceSecurity);

        $user = $this->prophesize(UserInterface::class);

        $security = $this->prophesize(Security::class);
        $security->getUser()->willReturn($user->reveal());

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $resolver = new PageSelectionPropertyResolver(
            $security->reveal(),
            [PermissionTypes::VIEW => 64],
            $requestAnalyzer->reveal()
        );

        $contentView = $resolver->resolve(['1'], 'en', [
            'properties' => [
                'property1' => 'value1',
            ],
        ]);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);

        $metadata = $content[0]->getMetadata();
        $this->assertSame([
            'properties' => [
                'property1' => 'value1',
            ],
            'filters' => [
                'locale' => 'en',
                'stage' => 'live',
                'permissionConfig' => [
                    'user' => $user->reveal(),
                    'permission' => 64,
                ],
            ],
        ], $metadata);
    }
}
