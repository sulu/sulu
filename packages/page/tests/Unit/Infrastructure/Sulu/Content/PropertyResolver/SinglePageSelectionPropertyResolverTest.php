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
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\SinglePageSelectionPropertyResolver;
use Symfony\Bundle\SecurityBundle\Security;

#[CoversClass(SinglePageSelectionPropertyResolver::class)]
class SinglePageSelectionPropertyResolverTest extends TestCase
{
    use ProphecyTrait;

    private SinglePageSelectionPropertyResolver $resolver;
    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private ObjectProphecy $requestAnalyzer;

    public function setUp(): void
    {
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->requestAnalyzer->getWebspace()->willReturn(null);

        $this->resolver = new SinglePageSelectionPropertyResolver(
            null,
            null,
            $this->requestAnalyzer->reveal()
        );
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve(null, 'en');

        $this->assertNull($contentView->getContent());
        $this->assertSame(['id' => null], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve(null, 'en', ['custom' => 'params']);

        $this->assertNull($contentView->getContent());
        $this->assertSame([
            'id' => null,
            'custom' => 'params',
        ], $contentView->getView());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertNull($contentView->getContent());
        $this->assertSame(['id' => null], $contentView->getView());
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
        yield 'multi_value' => [[1]];
        yield 'object' => [(object) [1]];
    }

    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(int|string $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);
        $this->assertSame($data, $content->getId());
        $this->assertSame('page', $content->getResourceLoaderKey());

        $this->assertSame(['id' => $data], $contentView->getView());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame($data, $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }

    /**
     * @return iterable<array{
     *     0: string,
     * }>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'string' => ['2'];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve('1', 'en', ['resourceLoader' => 'custom_Page']);

        $content = $contentView->getContent();

        $this->assertInstanceOf(ResolvableResource::class, $content);
        $this->assertSame('1', $content->getId());
        $this->assertSame('custom_Page', $content->getResourceLoaderKey());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame('1', $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }

    public function testResolveWithMetadata(): void
    {
        $contentView = $this->resolver->resolve('1', 'en', [
            'properties' => [
                'property1' => 'value1',
                'property2' => 'value2',
            ],
        ]);

        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);

        $this->assertSame([
            'properties' => [
                'property1' => 'value1',
                'property2' => 'value2',
            ],
            'filters' => [
                'locale' => 'en',
                'stage' => 'live',
            ],
        ], $content->getMetadata());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame('1', $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }

    public function testResolveWithoutMetadata(): void
    {
        $contentView = $this->resolver->resolve('1', 'en');

        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);

        $this->assertSame([
            'properties' => null,
            'filters' => [
                'locale' => 'en',
                'stage' => 'live',
            ],
        ], $content->getMetadata());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame('1', $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }

    public function testResolveWithEmptyMetadata(): void
    {
        $metadata = new FieldMetadata('test_field');

        $contentView = $this->resolver->resolve('1', 'en', ['metadata' => $metadata]);

        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);

        $this->assertSame([
            'properties' => null,
            'filters' => [
                'locale' => 'en',
                'stage' => 'live',
            ],
        ], $content->getMetadata());

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

        $resolver = new SinglePageSelectionPropertyResolver(
            $security->reveal(),
            [PermissionTypes::VIEW => 64],
            $requestAnalyzer->reveal()
        );

        $contentView = $resolver->resolve('1', 'en');

        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);

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
        ], $content->getMetadata());
    }

    public function testResolveWithNonSecureWebspace(): void
    {
        $webspace = new Webspace();

        $user = $this->prophesize(UserInterface::class);

        $security = $this->prophesize(Security::class);
        $security->getUser()->willReturn($user->reveal());

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $resolver = new SinglePageSelectionPropertyResolver(
            $security->reveal(),
            [PermissionTypes::VIEW => 64],
            $requestAnalyzer->reveal()
        );

        $contentView = $resolver->resolve('1', 'en');

        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);

        $metadata = $content->getMetadata();
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

        $resolver = new SinglePageSelectionPropertyResolver(
            $security->reveal(),
            [PermissionTypes::VIEW => 64],
            $requestAnalyzer->reveal()
        );

        $contentView = $resolver->resolve('1', 'en', [
            'properties' => [
                'property1' => 'value1',
            ],
        ]);

        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);

        $metadata = $content->getMetadata();
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
