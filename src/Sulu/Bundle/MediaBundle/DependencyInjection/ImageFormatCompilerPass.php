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

use Sulu\Bundle\MediaBundle\Media\FormatLoader\XmlFormatLoader;
use Symfony\Component\Config\FileLocator;
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
        $container->setParameter(
            'sulu_media.image.formats',
            $this->loadThemeFormats($container->getParameter('sulu_media.format_manager.default_imagine_options'))
        );
    }

    /**
     * @param array $defaultOptions
     *
     * @return array
     */
    protected function loadThemeFormats($defaultOptions)
    {
        $activeFormats = [];
        $this->setFormatsFromFile(__DIR__ . '/../Resources/config/image-formats.xml', $activeFormats, $defaultOptions);

        $activeTheme = $this->container->get('liip_theme.active_theme');
        $bundles = $this->container->getParameter('kernel.bundles');
        $configPaths = $this->container->getParameter('sulu_media.format_manager.config_paths');
        $defaultConfigPath = 'config/image-formats.xml';

        foreach ($activeTheme->getThemes() as $theme) {
            foreach ($bundles as $bundleName => $bundle) {
                $reflector = new \ReflectionClass($bundle);
                $configPath = $defaultConfigPath;
                if (isset($configPaths[$theme])) {
                    $configPath = $configPaths[$theme];
                }
                $fullPath = sprintf(
                    '%s/Resources/themes/%s/%s',
                    dirname($reflector->getFileName()),
                    $theme,
                    $configPath
                );

                if (file_exists($fullPath)) {
                    $this->setFormatsFromFile($fullPath, $activeFormats, $defaultOptions);
                }
            }
        }

        return $activeFormats;
    }

    /**
     * @param $fullPath
     * @param $activeFormats
     * @param $defaultOptions
     */
    protected function setFormatsFromFile($fullPath, &$activeFormats, $defaultOptions)
    {
        $folder = dirname($fullPath);
        $fileName = basename($fullPath);

        $locator = new FileLocator($folder);
        $loader = new XmlFormatLoader($locator);
        $loader->setDefaultOptions($defaultOptions);
        $themeFormats = $loader->load($fileName);
        foreach ($themeFormats as $format) {
            if (isset($format['name']) && !array_key_exists($format['name'], $activeFormats)) {
                $activeFormats[$format['name']] = $format;
            }
        }
    }
}
