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

/**
 * Generates a sulu bundle.
 */
class SuluBundleGenerator extends BundleGenerator
{
    public function generateBundle($dir, $bundle, $basename, $structure, $parameters)
    {
        // Basic things
        // /SuluTestBundle.php
        $this->renderFile('bundle/Bundle.php.twig', $dir . '/' . $bundle . '.php', $parameters);
        // /DependencyInjection/SuluGeneratorExtension.php
        $this->renderFile('bundle/Extension.php.twig', $dir . '/DependencyInjection/' . $basename . 'Extension.php', $parameters);
        // /DependencyInjection/Configuration.php
        $this->renderFile('bundle/Configuration.php.twig', $dir . '/DependencyInjection/Configuration.php', $parameters);

        /*
         * Sulu Specific.
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
        $this->renderFile('sulu/config/services.xml.twig', $dir . '/Resources/config/services.xml', $parameters);

        // Root files
        // Composer: /composer.json
        $this->renderFile('sulu/other/composer.json.twig', $dir . '/composer.json', $parameters);
        // Git: /.gitignore
        $this->renderFile('sulu/other/gitignore.twig', $dir . '/.gitignore', $parameters);
        // Grunt: /Gruntfile.js
        $this->renderFile('sulu/other/Gruntfile.js.twig', $dir . '/Gruntfile.js', $parameters);
        // Grunt: /LICENSE
        $this->renderFile('sulu/other/LICENSE.twig', $dir . '/LICENSE', $parameters);
        // NPM: /package.json
        $this->renderFile('sulu/other/package.json.twig', $dir . '/package.json', $parameters);
        // PHPUNIT: /phpunit.xml.dist
        $this->renderFile('sulu/other/phpunit.xml.dist.twig', $dir . '/phpunit.xml.dist', $parameters);
        // Github: /README.md
        $this->renderFile('sulu/other/README.md.twig', $dir . '/README.md', $parameters);

        // Public Files
        // Main: /Resources/public/js/main.js
        $this->renderFile('sulu/public/main.js.twig', $dir . '/Resources/public/js/main.js', $parameters);

        // Travis Files
        // /.travis.yml
        $this->renderFile('sulu/travis/.travis.yml.twig', $dir . '/.travis.yml', $parameters);
        // /Tests/bootstrap.php
        $this->renderFile('sulu/travis/Tests/bootstrap.php.twig', $dir . '/Tests/bootstrap.php', $parameters);
        // /Tests/Resources/app/AppKernel.php
        $this->renderFile('sulu/travis/Tests/app/AppKernel.php.twig', $dir . '/Tests/app/AppKernel.php', $parameters);
        // /tests/resources/app/config/routing.yml
        $this->renderfile('sulu/travis/Tests/app/config/routing.yml.twig', $dir . '/Tests/app/config/routing.yml', $parameters);
        // /tests/resources/app/Resources/webspaces/sulu.io.xml
        $this->renderfile('sulu/travis/Tests/app/Resources/webspaces/sulu.io.xml.twig', $dir . '/Tests/app/Resources/webspaces/sulu.io.xml', $parameters);

        // Basic Structure
        if ($structure) {
            $this->getFileSystem()->mkdir($dir . '/Controller');
            $this->getFileSystem()->touch($dir . '/Controller/.empty');
            $this->getFileSystem()->mkdir($dir . '/Entity');
            $this->getFileSystem()->touch($dir . '/Entity/.empty');
            $this->getFileSystem()->mkdir($dir . '/Tests');
            $this->getFileSystem()->mkdir($dir . '/Tests/Functional');
            $this->getFileSystem()->mkdir($dir . '/Tests/Functional/Controller');
            $this->getFileSystem()->touch($dir . '/Tests/Functional/Controller/.empty');
            $this->getFileSystem()->mkdir($dir . '/Resources/doc');
            $this->getFileSystem()->touch($dir . '/Resources/doc/index.rst');
            $this->getFileSystem()->mkdir($dir . '/Resources/views');
            $this->getFileSystem()->touch($dir . '/Resources/views/.empty');
            $this->getFileSystem()->mkdir($dir . '/Resources/public/js/collections');
            $this->getFileSystem()->touch($dir . '/Resources/public/js/collections/.empty');
            $this->getFileSystem()->mkdir($dir . '/Resources/public/js/components');
            $this->getFileSystem()->touch($dir . '/Resources/public/js/components/.empty');
            $this->getFileSystem()->mkdir($dir . '/Resources/public/js/model');
            $this->getFileSystem()->touch($dir . '/Resources/public/js/model/.empty');
            $this->renderFile('bundle/messages.fr.xlf', $dir . '/Resources/translations/messages.fr.xlf', $parameters);
        }
    }
}
