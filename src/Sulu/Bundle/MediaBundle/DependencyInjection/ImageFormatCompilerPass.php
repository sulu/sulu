<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use Sulu\Bundle\MediaBundle\Media\FormatLoader\XmlFormatLoader10;
use Sulu\Bundle\MediaBundle\Media\FormatLoader\XmlFormatLoader11;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ImageFormatCompilerPass.
 */
class ImageFormatCompilerPass implements CompilerPassInterface
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->container = $container;

        $formats = $this->loadImageFormats(
            $container->getParameter('sulu_media.format_manager.default_imagine_options')
        );
        if ($container->hasParameter('sulu_media.image.formats')) {
            $formats = array_merge($container->getParameter('sulu_media.image.formats'), $formats);
        }

        $container->setParameter('sulu_media.image.formats', $formats);
    }

    /**
     * @param array $globalOptions
     *
     * @return array
     */
    protected function loadImageFormats($globalOptions)
    {
        $activeFormats = [];

        $files = $this->container->getParameter('sulu_media.image_format_files');
        foreach ($files as $file) {
            if (file_exists($file)) {
                $this->setFormatsFromFile($file, $activeFormats, $globalOptions);
            }
        }

        return $activeFormats;
    }

    /**
     * @param $fullPath
     * @param $activeFormats
     * @param $globalOptions
     */
    protected function setFormatsFromFile($fullPath, &$activeFormats, $globalOptions)
    {
        $folder = dirname($fullPath);
        $fileName = basename($fullPath);

        $locator = new FileLocator($folder);

        $xmlLoader10 = new XmlFormatLoader10($locator);
        $xmlLoader11 = new XmlFormatLoader11($locator);
        $xmlLoader10->setGlobalOptions($globalOptions);
        $xmlLoader11->setGlobalOptions($globalOptions);

        $resolver = new LoaderResolver([$xmlLoader10, $xmlLoader11]);
        $loader = new DelegatingLoader($resolver);

        $themeFormats = $loader->load($fileName);
        foreach ($themeFormats as $format) {
            $activeFormats[$format['key']] = $format;
        }
    }
}
