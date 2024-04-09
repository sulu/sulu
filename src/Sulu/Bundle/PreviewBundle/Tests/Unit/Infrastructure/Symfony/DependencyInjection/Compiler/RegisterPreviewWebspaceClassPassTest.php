<?php

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Infrastructure\Symfony\DependencyInjection\Compiler;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\DependencyInjection\Compiler\RegisterPreviewWebspaceClassPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterPreviewWebspaceClassPassTest extends TestCase
{
    /** @var ContainerBuilder&MockObject */
    private $containerBuilderMock;

    public function testShouldNotOverrideWebspaceCacheClass(): void
    {
        $this->containerBuilderMock
            ->method('hasExtension')
            ->with('sulu_core')
            ->willReturn(false);

        $this->containerBuilderMock
            ->expects($this->never())
            ->method('setParameter')
            ->with('sulu_core.webspace.cache_class');

        $this->createCompilerPass()->process($this->containerBuilderMock);
    }

    public function testShouldOverrideWebspaceCacheClass(): void
    {
        $this->containerBuilderMock
            ->method('hasExtension')
            ->with('sulu_core')
            ->willReturn(true);

        $this->containerBuilderMock
            ->method('hasParameter')
            ->with('sulu.preview')
            ->willReturn(true);

        $this->containerBuilderMock
            ->method('getParameter')
            ->with('sulu.preview')
            ->willReturn(true);

        $this->containerBuilderMock
            ->method('getParameter')
            ->with('sulu_core.webspace.cache_class')
            ->willReturn('websiteWebspaceCollectionCache');

        $this->containerBuilderMock
            ->expects($this->never())
            ->method('setParameter')
            ->with(
                'sulu_core.webspace.cache_class',
                'previewwebsiteWebspaceCollectionCache'
            );
    }

    private function createCompilerPass(): RegisterPreviewWebspaceClassPass
    {
        return new RegisterPreviewWebspaceClassPass();
    }

    protected function setUp(): void
    {
        $this->containerBuilderMock = $this->createMock(ContainerBuilder::class);
    }
}
