<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\View;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewParameterProviderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGenerator;
use Sulu\Bundle\AdminBundle\Admin\View\ViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Exception\ResourceViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewParameterNotFoundException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResourceViewUrlGeneratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ViewUrlGeneratorInterface>
     */
    private ObjectProphecy $viewUrlGenerator;

    public function setUp(): void
    {
        $this->viewUrlGenerator = $this->prophesize(ViewUrlGeneratorInterface::class);
    }

    /**
     * @param array<string, array{views?: array<string, string>}> $resources
     * @param array<string, ResourceViewParameterProviderInterface> $viewParameterProviders
     */
    private function createResourceViewUrlGenerator(array $resources, array $viewParameterProviders = []): ResourceViewUrlGenerator
    {
        return new ResourceViewUrlGenerator(
            $this->viewUrlGenerator->reveal(),
            $resources,
            new ServiceLocator(\array_map(
                static fn (ResourceViewParameterProviderInterface $provider) => static fn () => $provider,
                $viewParameterProviders,
            )),
        );
    }

    public function testGenerate(): void
    {
        $resources = [
            'contacts' => [
                'views' => [
                    'detail' => 'sulu_contact.contact_edit_form.details',
                ],
            ],
        ];

        $this->viewUrlGenerator->generate(
            'sulu_contact.contact_edit_form.details',
            ['id' => 1],
            UrlGeneratorInterface::ABSOLUTE_PATH
        )->willReturn('/admin/#/contacts/1/details');

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator($resources);

        $this->assertSame(
            '/admin/#/contacts/1/details',
            $resourceViewUrlGenerator->generate('contacts', 'detail', ['id' => 1])
        );
    }

    public function testGenerateWithReferenceType(): void
    {
        $resources = [
            'contacts' => [
                'views' => [
                    'detail' => 'sulu_contact.contact_edit_form.details',
                ],
            ],
        ];

        $this->viewUrlGenerator->generate(
            'sulu_contact.contact_edit_form.details',
            ['id' => 1],
            UrlGeneratorInterface::ABSOLUTE_URL
        )->willReturn('https://example.org/admin/#/contacts/1/details');

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator($resources);

        $this->assertSame(
            'https://example.org/admin/#/contacts/1/details',
            $resourceViewUrlGenerator->generate('contacts', 'detail', ['id' => 1], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    public function testGenerateThrowsExceptionForUnknownResourceKey(): void
    {
        $this->expectException(ResourceViewNotFoundException::class);

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator([]);
        $resourceViewUrlGenerator->generate('not_existing', 'detail');
    }

    public function testGenerateThrowsExceptionForUnconfiguredResourceView(): void
    {
        $this->expectException(ResourceViewNotFoundException::class);

        $resources = [
            'contacts' => [
                'views' => [
                    'list' => 'sulu_contact.contacts',
                ],
            ],
        ];

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator($resources);
        $resourceViewUrlGenerator->generate('contacts', 'detail');
    }

    public function testGeneratePropagatesViewNotFoundException(): void
    {
        $this->expectException(ViewNotFoundException::class);

        $resources = [
            'contacts' => [
                'views' => [
                    'detail' => 'sulu_contact.not_existing',
                ],
            ],
        ];

        $this->viewUrlGenerator->generate('sulu_contact.not_existing', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willThrow(new ViewNotFoundException('sulu_contact.not_existing'));

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator($resources);
        $resourceViewUrlGenerator->generate('contacts', 'detail');
    }

    public function testGenerateResolvesViewNamePlaceholderFromViewParameters(): void
    {
        $resources = [
            'snippets' => [
                'views' => [
                    'detail' => 'sulu_snippet.snippet.edit_tabs_{group}',
                ],
            ],
        ];

        $this->viewUrlGenerator->generate(
            'sulu_snippet.snippet.edit_tabs_alternate',
            ['id' => 'abc', 'locale' => 'en', 'group' => 'alternate'],
            UrlGeneratorInterface::ABSOLUTE_PATH
        )->willReturn('/admin/#/snippets/en/alternate/abc');

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator($resources);

        $this->assertSame(
            '/admin/#/snippets/en/alternate/abc',
            $resourceViewUrlGenerator->generate('snippets', 'detail', ['id' => 'abc', 'locale' => 'en', 'group' => 'alternate'])
        );
    }

    public function testGenerateResolvesViewNamePlaceholderFromViewParameterProvider(): void
    {
        $resources = [
            'snippets' => [
                'views' => [
                    'detail' => 'sulu_snippet.snippet.edit_tabs_{group}',
                ],
            ],
        ];

        $viewParameterProvider = $this->prophesize(ResourceViewParameterProviderInterface::class);
        $viewParameterProvider->getViewParameters(['id' => 'abc', 'locale' => 'en'])
            ->willReturn(['group' => 'alternate', 'locale' => 'de']);

        $this->viewUrlGenerator->generate(
            'sulu_snippet.snippet.edit_tabs_alternate',
            ['group' => 'alternate', 'locale' => 'en', 'id' => 'abc'],
            UrlGeneratorInterface::ABSOLUTE_PATH
        )->willReturn('/admin/#/snippets/en/alternate/abc');

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator(
            $resources,
            ['snippets' => $viewParameterProvider->reveal()],
        );

        $this->assertSame(
            '/admin/#/snippets/en/alternate/abc',
            $resourceViewUrlGenerator->generate('snippets', 'detail', ['id' => 'abc', 'locale' => 'en'])
        );
    }

    public function testGenerateAddsProvidedParametersForViewWithoutPlaceholder(): void
    {
        $resources = [
            'pages' => [
                'views' => [
                    'detail' => 'app.page_edit_form',
                ],
            ],
        ];

        $viewParameterProvider = $this->prophesize(ResourceViewParameterProviderInterface::class);
        $viewParameterProvider->getViewParameters(['id' => '3'])->willReturn(['segment' => 'blog']);

        $this->viewUrlGenerator->generate(
            'app.page_edit_form',
            ['segment' => 'blog', 'id' => '3'],
            UrlGeneratorInterface::ABSOLUTE_PATH
        )->willReturn('/admin/#/pages/blog/3');

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator(
            $resources,
            ['pages' => $viewParameterProvider->reveal()],
        );

        $this->assertSame(
            '/admin/#/pages/blog/3',
            $resourceViewUrlGenerator->generate('pages', 'detail', ['id' => '3'])
        );
    }

    public function testGeneratePrefersPassedParametersOverProvidedParameters(): void
    {
        $resources = [
            'snippets' => [
                'views' => [
                    'detail' => 'sulu_snippet.snippet.edit_tabs_{group}',
                ],
            ],
        ];

        $viewParameterProvider = $this->prophesize(ResourceViewParameterProviderInterface::class);
        $viewParameterProvider->getViewParameters(['id' => 'abc', 'group' => 'default'])
            ->willReturn(['group' => 'alternate']);

        $this->viewUrlGenerator->generate(
            'sulu_snippet.snippet.edit_tabs_default',
            ['group' => 'default', 'id' => 'abc'],
            UrlGeneratorInterface::ABSOLUTE_PATH
        )->willReturn('/admin/#/snippets/en/default/abc');

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator(
            $resources,
            ['snippets' => $viewParameterProvider->reveal()],
        );

        $this->assertSame(
            '/admin/#/snippets/en/default/abc',
            $resourceViewUrlGenerator->generate('snippets', 'detail', ['id' => 'abc', 'group' => 'default'])
        );
    }

    public function testGenerateThrowsExceptionForUnresolvedViewNamePlaceholder(): void
    {
        $this->expectException(ViewParameterNotFoundException::class);
        $this->expectExceptionMessage('The parameter "group" is required to generate the url for the view "sulu_snippet.snippet.edit_tabs_{group}" but was not given.');

        $resources = [
            'snippets' => [
                'views' => [
                    'detail' => 'sulu_snippet.snippet.edit_tabs_{group}',
                ],
            ],
        ];

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator($resources);
        $resourceViewUrlGenerator->generate('snippets', 'detail', ['id' => 'abc']);
    }
}
