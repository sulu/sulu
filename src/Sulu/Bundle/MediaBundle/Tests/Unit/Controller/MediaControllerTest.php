<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGeneratorInterface;
use Sulu\Bundle\MediaBundle\Controller\MediaController;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\ListBuilderFactory\MediaListBuilderFactory;
use Sulu\Bundle\MediaBundle\Media\ListRepresentationFactory\MediaListRepresentationFactory;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Component\Rest\Exception\ReferencingResourcesFoundException;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MediaControllerTest extends TestCase
{
    public function testConstructWithoutResourceViewUrlGeneratorTriggersDeprecation(): void
    {
        $deprecations = $this->collectDeprecations(fn () => $this->createController());

        $this->assertSame(
            ['Since sulu/sulu 3.1: Instantiating MediaController without the $resourceViewUrlGenerator argument is deprecated, the resources referencing a media are listed without a link without it.'],
            $deprecations,
        );
    }

    public function testConstructWithResourceViewUrlGeneratorTriggersNoDeprecation(): void
    {
        $deprecations = $this->collectDeprecations(
            fn () => $this->createController($this->createStub(ResourceViewUrlGeneratorInterface::class))
        );

        $this->assertSame([], $deprecations);
    }

    public function testDeleteIgnoresRouterAttributesWithoutStringValue(): void
    {
        $resourceViewUrlGenerator = $this->createMock(ResourceViewUrlGeneratorInterface::class);
        $resourceViewUrlGenerator->expects($this->once())
            ->method('generate')
            ->with('pages', 'detail', ['webspace' => 'sulu_io', 'id' => 'page-uuid-1'])
            ->willReturn('/admin/#/webspaces/sulu_io/pages/en/page-uuid-1');

        $referencingResources = $this->deleteReferencedMedia(
            $resourceViewUrlGenerator,
            [$this->createReference('page-uuid-1', 'en', ['webspace' => 'sulu_io', 'locale' => null, 'empty' => '', 'nested' => ['en']])],
        )->getReferencingResources();

        $this->assertSame('/admin/#/webspaces/sulu_io/pages/en/page-uuid-1', $referencingResources[0]['url'] ?? null);
    }

    public function testDeleteListsResourceWithoutUrlIfUrlGenerationFails(): void
    {
        $resourceViewUrlGenerator = $this->createStub(ResourceViewUrlGeneratorInterface::class);
        $resourceViewUrlGenerator->method('generate')->willThrowException(new \RuntimeException('Database is gone'));

        $referencingResources = $this->deleteReferencedMedia(
            $resourceViewUrlGenerator,
            [$this->createReference('page-uuid-1', 'en', ['webspace' => 'sulu_io'])],
        )->getReferencingResources();

        $this->assertCount(1, $referencingResources);
        $this->assertSame('page-uuid-1', $referencingResources[0]['id']);
        $this->assertNull($referencingResources[0]['url'] ?? null);
    }

    public function testDeleteLinksToFirstLocaleIfRequestLocaleHasNoReference(): void
    {
        $resourceViewUrlGenerator = $this->createMock(ResourceViewUrlGeneratorInterface::class);
        $resourceViewUrlGenerator->expects($this->exactly(2))
            ->method('generate')
            ->with('pages', 'detail', ['locale' => 'de', 'id' => 'page-uuid-1'])
            ->willReturn('/de');

        $references = [
            $this->createReference('page-uuid-1', 'en', ['locale' => 'en'], 'Team'),
            $this->createReference('page-uuid-1', 'de', ['locale' => 'de'], 'Team (Deutsch)'),
        ];

        foreach ([$references, \array_reverse($references)] as $orderedReferences) {
            $referencingResources = $this->deleteReferencedMedia($resourceViewUrlGenerator, $orderedReferences, 'fr')->getReferencingResources();

            $this->assertSame('Team (Deutsch)', $referencingResources[0]['title']);
            $this->assertSame('/de', $referencingResources[0]['url'] ?? null);
        }
    }

    public function testDeleteListsOnlyTheFirstResourcesAndCountsAll(): void
    {
        $resourceViewUrlGenerator = $this->createMock(ResourceViewUrlGeneratorInterface::class);
        $resourceViewUrlGenerator->expects($this->exactly(5))
            ->method('generate')
            ->willReturn('/admin');

        $references = [];
        for ($i = 25; $i >= 1; --$i) {
            $references[] = $this->createReference('page-uuid-' . $i, 'en', [], \sprintf('Team %02d', $i));
        }

        $exception = $this->deleteReferencedMedia($resourceViewUrlGenerator, $references);

        $this->assertSame(25, $exception->getReferencingResourcesCount());
        $this->assertSame(
            ['Team 01', 'Team 02', 'Team 03', 'Team 04', 'Team 05'],
            \array_column($exception->getReferencingResources(), 'title'),
        );
        $this->assertSame(['/admin'], \array_unique(\array_column($exception->getReferencingResources(), 'url')));
    }

    /**
     * @param array<string, mixed> $routerAttributes
     *
     * @return array<string, mixed>
     */
    private function createReference(
        string $resourceId,
        string $locale,
        array $routerAttributes,
        string $title = 'Team',
    ): array {
        return [
            'referenceResourceKey' => 'pages',
            'referenceResourceId' => $resourceId,
            'referenceTitle' => $title,
            'referenceLocale' => $locale,
            'referenceRouterAttributes' => $routerAttributes,
        ];
    }

    /**
     * @param array<array<string, mixed>> $references
     */
    private function deleteReferencedMedia(
        ResourceViewUrlGeneratorInterface $resourceViewUrlGenerator,
        array $references,
        ?string $locale = null,
    ): ReferencingResourcesFoundException {
        $referenceRepository = $this->createStub(ReferenceRepositoryInterface::class);
        $referenceRepository->method('findFlatBy')->willReturn($references);

        $mediaManager = $this->createStub(MediaManagerInterface::class);
        $mediaManager->method('getById')->willThrowException(new MediaNotFoundException('1'));

        $requestStack = new RequestStack();
        $requestStack->push(new Request(null !== $locale ? ['locale' => $locale] : []));

        $controller = new MediaController(
            $this->createStub(ViewHandlerInterface::class),
            $this->createStub(TokenStorageInterface::class),
            $mediaManager,
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(StorageInterface::class),
            $this->createStub(SecurityCheckerInterface::class),
            $this->createStub(FieldDescriptorFactoryInterface::class),
            'Sulu\Bundle\MediaBundle\Entity\Media',
            $this->createStub(MediaListBuilderFactory::class),
            $this->createStub(MediaListRepresentationFactory::class),
            $referenceRepository,
            $requestStack,
            $resourceViewUrlGenerator,
        );

        try {
            $controller->deleteAction(1);
        } catch (ReferencingResourcesFoundException $exception) {
            return $exception;
        }

        $this->fail('The media is referenced, the delete should have been refused.');
    }

    private function createController(?ResourceViewUrlGeneratorInterface $resourceViewUrlGenerator = null): MediaController
    {
        return new MediaController(
            $this->createStub(ViewHandlerInterface::class),
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(MediaManagerInterface::class),
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(StorageInterface::class),
            $this->createStub(SecurityCheckerInterface::class),
            $this->createStub(FieldDescriptorFactoryInterface::class),
            'Sulu\Bundle\MediaBundle\Entity\Media',
            $this->createStub(MediaListBuilderFactory::class),
            $this->createStub(MediaListRepresentationFactory::class),
            $this->createStub(ReferenceRepositoryInterface::class),
            new RequestStack(),
            $resourceViewUrlGenerator,
        );
    }

    /**
     * @return string[]
     */
    private function collectDeprecations(callable $callback): array
    {
        $deprecations = [];
        \set_error_handler(static function(int $errorNumber, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $callback();
        } finally {
            \restore_error_handler();
        }

        return $deprecations;
    }
}
