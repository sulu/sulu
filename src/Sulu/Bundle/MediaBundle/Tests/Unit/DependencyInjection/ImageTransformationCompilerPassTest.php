<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\ImageTransformationCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Test the image transformation compiler pass.
 */
class ImageTransformationCompilerPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ImageTransformationCompilerPass());
    }

    /**
     * @test
     */
    public function ifCompilerPassCollectsServicesByAddingMethodCallsTheseWillExist(): void
    {
        $transformationManager = new Definition();
        $this->setDefinition(ImageTransformationCompilerPass::POOL_SERVICE_ID, $transformationManager);

        $transformation = new Definition();
        $transformation->addTag(ImageTransformationCompilerPass::TAG, ['alias' => 'resize']);
        $this->setDefinition('sulu_media.image.transformation.resize', $transformation);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            ImageTransformationCompilerPass::POOL_SERVICE_ID,
            'add',
            [
                new Reference('sulu_media.image.transformation.resize'),
                'resize',
            ]
        );
    }
}
