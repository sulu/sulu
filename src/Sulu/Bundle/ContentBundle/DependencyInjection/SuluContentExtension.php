<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluContentExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        $extensions = $container->getExtensions();

        if (isset($extensions['sulu_core'])) {
            $prepend = array(
                'content' => array(
                    'structure' => array(
                        'paths' => array(
                            array(
                                'path' => __DIR__ . '/../Content/templates',
                                'type' => 'page',
                                'internal' => true,
                            ),
                        ),
                    ),
                ),
            );

            $container->prependExtensionConfig('sulu_core', $prepend);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $this->processPreview($config, $loader, $container);
        $this->processContentTypeTemplates($config, $container);

        if (isset($bundles['SuluSearchBundle'])) {
            $this->processSearch($config, $loader, $container);
        }

        $loader->load('services.xml');
        $loader->load('content_types.xml');

    }

    private function processPreview($config, LoaderInterface $loader, ContainerBuilder $container)
    {
        $container->setParameter('sulu.content.preview.mode', $config['preview']['mode']);
        $container->setParameter('sulu.content.preview.websocket', $config['preview']['websocket']);
        $container->setParameter('sulu.content.preview.delay', $config['preview']['delay']);
        $errorTemplate = null;
        if (isset($config['preview']['error_template'])) {
            $errorTemplate = $config['preview']['error_template'];
        }
        $container->setParameter(
            'sulu.content.preview.error_template',
            $errorTemplate
        );
        $loader->load('preview.xml');
    }

    private function processContentTypeTemplates($config, ContainerBuilder $container)
    {
        $container->setParameter(
            'sulu.content.type.smart_content.template',
            $config['types']['smart_content']['template']
        );
        $container->setParameter(
            'sulu.content.type.internal_links.template',
            $config['types']['internal_links']['template']
        );
        $container->setParameter(
            'sulu.content.type.single_internal_link.template',
            $config['types']['single_internal_link']['template']
        );
        $container->setParameter(
            'sulu.content.type.phone.template',
            $config['types']['phone']['template']
        );
        $container->setParameter(
            'sulu.content.type.password.template',
            $config['types']['password']['template']
        );
        $container->setParameter(
            'sulu.content.type.url.template',
            $config['types']['url']['template']
        );
        $container->setParameter(
            'sulu.content.type.email.template',
            $config['types']['email']['template']
        );
        $container->setParameter(
            'sulu.content.type.date.template',
            $config['types']['date']['template']
        );
        $container->setParameter(
            'sulu.content.type.time.template',
            $config['types']['time']['template']
        );
        $container->setParameter(
            'sulu.content.type.color.template',
            $config['types']['color']['template']
        );
        $container->setParameter(
            'sulu.content.type.checkbox.template',
            $config['types']['checkbox']['template']
        );
    }

    private function processSearch($config, LoaderInterface $loader, ContainerBuilder $container)
    {
        $container->setParameter('sulu_content.search.mapping', $config['search']['mapping']);
        $loader->load('search.xml');
    }
}
