<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
            ->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnOnConsecutiveCalls(true, 'websiteWebspaceCollectionCache');

        $this->containerBuilderMock
            ->expects($this->once())
            ->method('setParameter')
            ->with(
                'sulu_core.webspace.cache_class',
                'previewwebsiteWebspaceCollectionCache'
            );

        $this->createCompilerPass()->process($this->containerBuilderMock);
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
