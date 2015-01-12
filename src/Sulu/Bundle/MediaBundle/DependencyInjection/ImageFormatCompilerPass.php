<?php
/*
 * This file is part of the Sulu CMS.
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
 * Class ImageFormatCompilerPass
 * @package Sulu\Bundle\MediaBundle\DependencyInjection
 */
class ImageFormatCompilerPass implements CompilerPassInterface
{
    /**
     * @var ContainerBuilder $container
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
     * @return array
     */
    protected function loadThemeFormats($defaultOptions)
    {
        $activeFormats = array();
        $suluFormats = $this->container->getParameter('sulu_media.image.formats');
        if (is_array($suluFormats)) {
            $activeFormats = $suluFormats;
        }

        $activeTheme = $this->container->get('liip_theme.active_theme');
        $bundles = $this->container->getParameter('kernel.bundles');

        foreach ($activeTheme->getThemes() as $theme) {
            foreach ($bundles as $bundle => $class) {
                $reflector = new \ReflectionClass($class);
                if ($reflector->getFileName() &&
                    file_exists(
                        dirname($reflector->getFileName()) . '/Resources/themes/' . $theme . '/config/image-formats.xml'
                    )
                ) {
                    $themePath = dirname($reflector->getFileName())
                        . '/Resources/themes/' . $theme . '/config/image-formats.xml';

                    $folder = dirname($themePath);
                    $fileName = basename($themePath);

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
        }

        return $activeFormats;
    }
}
