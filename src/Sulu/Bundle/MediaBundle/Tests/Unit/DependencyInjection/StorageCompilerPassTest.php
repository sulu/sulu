<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\StorageCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Sulu\Bundle\MediaBundle\Media\Storage\LocalStorage;

/**
 * Test the image command compiler pass.
 */
class StorageCompilerPassTest extends AbstractCompilerPassTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new StorageCompilerPass());
    }

    /**
     * @test
     */
    public function if_compiler_pass_collects_services_by_adding_method_calls_these_will_exist()
    {
        $storageManager = new Definition();
        $this->setDefinition('sulu_media.storage_manager', $storageManager);

        $this->setParameter('sulu_media.storage.adapters', [
            'test' => [
                'type' => 'local_test',
                'segments' => '10',
                'uploadPath' => '/uploads/media',
            ],
        ]);

        $this->setParameter(
            'sulu_media.storage.adapter.local_test.class',
            'Sulu\Bundle\MediaBundle\Media\Storage\LocalStorage'
        );

        $adapter = new Definition();
        $adapter->addTag('sulu_media.storage_adapter', ['alias' => 'local_test']);
        $adapter->setClass('%sulu_media.storage.adapter.local_test.class%');
        $this->setDefinition('sulu_media.storage.adapter.local_test', $adapter);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_media.storage_manager',
            'add',
            [
                new Reference('sulu_media.test_storage'),
                'test',
            ]
        );
    }
}
