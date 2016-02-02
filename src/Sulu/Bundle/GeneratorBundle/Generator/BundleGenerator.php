<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\GeneratorBundle\Generator;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Generates a sulu bundle.
 */
abstract class BundleGenerator extends Generator
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function generate($namespace, $bundle, $dir, $structure, $route)
    {
        $dir .= '/' . strtr($namespace, '\\', '/');
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.', realpath($dir)));
            }
            $files = scandir($dir);
            if ($files != ['.', '..']) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not empty.', realpath($dir)));
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.', realpath($dir)));
            }
        }

        $basename = substr($bundle, 0, -6);
        $parameters = [
            'namespace' => $namespace,
            'bundle' => $bundle,
            'format' => 'xml',
            'bundle_basename' => $basename,
            'basename' => $basename,
            'extension_alias' => Container::underscore($basename),
            'extensionalias' => str_replace('_', '', Container::underscore($basename)),
            'route' => $route,
        ];

        $this->generateBundle($dir, $bundle, $basename, $structure, $parameters);

//        $this->renderFile('bundle/Bundle.php.twig', $dir.'/'.$bundle.'.php', $parameters);
//        $this->renderFile('bundle/Extension.php.twig', $dir.'/DependencyInjection/'.$basename.'Extension.php', $parameters);
//        $this->renderFile('bundle/Configuration.php.twig', $dir.'/DependencyInjection/Configuration.php', $parameters);
//        $this->renderFile('bundle/DefaultController.php.twig', $dir.'/Controller/DefaultController.php', $parameters);
//        $this->renderFile('bundle/DefaultControllerTest.php.twig', $dir.'/Tests/Controller/DefaultControllerTest.php', $parameters);
//        $this->renderFile('bundle/index.html.twig.twig', $dir.'/Resources/views/Default/index.html.twig', $parameters);

//        if ('xml' === $format || 'annotation' === $format) {
//            $this->renderFile('bundle/services.xml.twig', $dir.'/Resources/config/services.xml', $parameters);
//        } else {
//            $this->renderFile('bundle/services.'.$format.'.twig', $dir.'/Resources/config/services.'.$format, $parameters);
//        }
//
//        if ('annotation' != $format) {
//            $this->renderFile('bundle/routing.'.$format.'.twig', $dir.'/Resources/config/routing.'.$format, $parameters);
//        }
//
//        if ($structure) {
//            $this->renderFile('bundle/messages.fr.xlf', $dir.'/Resources/translations/messages.fr.xlf', $parameters);
//
//            $this->filesystem->mkdir($dir.'/Resources/doc');
//            $this->filesystem->touch($dir.'/Resources/doc/index.rst');
//            $this->filesystem->mkdir($dir.'/Resources/translations');
//            $this->filesystem->mkdir($dir.'/Resources/public/css');
//            $this->filesystem->mkdir($dir.'/Resources/public/images');
//            $this->filesystem->mkdir($dir.'/Resources/public/js');
//        }
    }

    abstract public function generateBundle($dir, $bundle, $basename, $structure, $parameters);

    protected function getFileSystem()
    {
        return $this->filesystem;
    }
}
