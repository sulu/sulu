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

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\S3ClientCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class S3ClientCompilerPassTest extends TestCase
{
    public function testAdditionalArgumentsAreMergedAndEmptyOnesRemoved(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sulu_media.media.storage', 's3');
        $container->setParameter('sulu_media.media.storage.s3.arguments', ['use_path_style_endpoint' => true]);
        $container->setDefinition(
            'sulu_media.storage.s3.client',
            (new Definition())->setArguments([['region' => 'eu-west-1', 'endpoint' => null]])
        );

        (new S3ClientCompilerPass())->process($container);

        $this->assertSame(
            ['region' => 'eu-west-1', 'use_path_style_endpoint' => true],
            $container->getDefinition('sulu_media.storage.s3.client')->getArgument(0)
        );
    }

    public function testOtherStorageIsIgnored(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sulu_media.media.storage', 'local');

        (new S3ClientCompilerPass())->process($container);

        $this->assertFalse($container->has('sulu_media.storage.s3.client'));
    }
}
