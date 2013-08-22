<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\GeneratorBundle\Generator;

/**
 * Generates a sulu bundle.
 */
class SuluBundleGenerator extends BundleGenerator
{
//
//        $this->renderFile('bundle/DefaultController.php.twig', $dir.'/Controller/DefaultController.php', $parameters);
//        $this->renderFile('bundle/DefaultControllerTest.php.twig', $dir.'/Tests/Controller/DefaultControllerTest.php', $parameters);
//        $this->renderFile('bundle/index.html.twig.twig', $dir.'/Resources/views/Default/index.html.twig', $parameters);


//


    public function generateBundle($dir, $bundle, $basename, $structure, $parameters)
    {
        // Basic things
        // /SuluTestBundle.php
        $this->renderFile('bundle/Bundle.php.twig', $dir . '/' . $bundle . '.php', $parameters);
        // /DependencyInjection/SuluGeneratorExtension.php
        $this->renderFile('bundle/Extension.php.twig', $dir . '/DependencyInjection/' . $basename . 'Extension.php', $parameters);
        // /DependencyInjection/Configuration.php
        $this->renderFile('bundle/Configuration.php.twig', $dir . '/DependencyInjection/Configuration.php', $parameters);

        /**
         * Sulu Specific
         */

        // Default Api Controller
        // TODO Default Api Controller

        // Admin class: /Admin/SuluTestAdmin.php
        $this->renderFile('sulu/admin/adminclass.php.twig', $dir . '/Admin/' . $basename . 'Admin.php', $parameters);

        // Configuration files
        // Routing: /Resources/config/routing.yml
        $this->renderFile('sulu/config/routing.yml.twig', $dir . '/Resources/config/routing.yml', $parameters);
        // API Routing: /Resources/config/routing_api.yml
        $this->renderFile('sulu/config/routing_api.yml.twig', $dir . '/Resources/config/routing_api.yml', $parameters);
        // Services: /Resources/config/services.yml
        $this->renderFile('sulu/config/services.yml.twig', $dir . '/Resources/config/services.yml', $parameters);

        // Root files
        // Composer: /composer.json
        $this->renderFile('sulu/other/composer.json', $dir.'/composer.json', $parameters);
        // Git: /.gitignore
        $this->renderFile('sulu/other/gitignore.twig', $dir.'/.gitignore', $parameters);
        // Grunt: /Gruntfile.js
        $this->renderFile('sulu/other/Gruntfile.js.twig', $dir.'/Gruntfile.js', $parameters);
        // Grunt: /LICENSE
        $this->renderFile('sulu/other/LICENSE.twig', $dir.'/LICENSE', $parameters);
        // NPM: /package.json
        $this->renderFile('sulu/other/package.json.twig', $dir.'/package.json', $parameters);
        // PHPUNIT: /phpunit.xml.dist
        $this->renderFile('sulu/other/phpunit.xml.dist.twig.twig', $dir.'/phpunit.xml.dist', $parameters);
        // Github: /README.md
        $this->renderFile('sulu/other/README.md.twig', $dir.'/README', $parameters);

        // Basic Structure
        if ($structure) {
            $this->getFileSystem()->mkdir($dir . '/Resources/doc');
            $this->getFileSystem()->touch($dir . '/Resources/doc/index.rst');
            $this->getFileSystem()->mkdir($dir . '/Resources/translations');
            $this->renderFile('bundle/messages.fr.xlf', $dir . '/Resources/translations/messages.fr.xlf', $parameters);
        }
    }
}