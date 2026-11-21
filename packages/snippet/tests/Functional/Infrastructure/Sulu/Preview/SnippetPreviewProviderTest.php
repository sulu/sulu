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

namespace Sulu\Snippet\Tests\Functional\Infrastructure\Sulu\Preview;

use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Preview\SnippetPreviewProvider;
use Sulu\Snippet\UserInterface\Controller\Website\SnippetPreviewController;

class SnippetPreviewProviderTest extends KernelTestCase
{
    /**
     * The preview tab of the form comes from ContentViewBuilderFactory, which enables it for a
     * resource key exactly when the registry holds a provider for it.
     */
    public function testSnippetsHaveAPreviewObjectProvider(): void
    {
        self::bootKernel();

        /** @var PreviewObjectProviderRegistryInterface $registry */
        $registry = self::getContainer()->get('sulu_preview.preview_object_provider_registry');

        $this->assertTrue($registry->hasPreviewObjectProvider(SnippetInterface::RESOURCE_KEY));
        $this->assertInstanceOf(
            SnippetPreviewProvider::class,
            $registry->getPreviewObjectProvider(SnippetInterface::RESOURCE_KEY)
        );
    }

    public function testDefaultsOfATemplateDeclaringAViewAreLeftAlone(): void
    {
        $defaults = [
            'object' => $this->createStub(TemplateInterface::class),
            'view' => 'views/snippets/snippet',
            '_controller' => 'Sulu\Content\UserInterface\Controller\Website\ContentController::indexAction',
        ];

        $this->assertSame($defaults, $this->applyFallback($defaults));
    }

    public function testATemplateWithoutAViewFallsBackToTheNoticeController(): void
    {
        $object = $this->createStub(TemplateInterface::class);
        $object->method('getTemplateKey')->willReturn('header-menu');

        $defaults = $this->applyFallback(['object' => $object, 'view' => null, '_controller' => null]);

        $this->assertSame(SnippetPreviewController::class . '::indexAction', $defaults['_controller']);
        $this->assertNull($defaults['view']);
        $this->assertSame('header-menu', $defaults['templateKey']);
    }

    /**
     * An unknown snippet leaves the parent with nothing to describe, and there is no pane to
     * fill in that case either.
     */
    public function testEmptyDefaultsAreLeftAlone(): void
    {
        $this->assertSame([], $this->applyFallback([]));
    }

    /**
     * @param array<string, mixed> $defaults
     *
     * @return array<string, mixed>
     */
    private function applyFallback(array $defaults): array
    {
        $provider = new class() extends SnippetPreviewProvider {
            public function __construct()
            {
            }

            /**
             * @param array<string, mixed> $defaults
             *
             * @return array<string, mixed>
             */
            public function expose(array $defaults): array
            {
                return $this->applyMissingViewFallback($defaults);
            }
        };

        return $provider->expose($defaults);
    }
}
