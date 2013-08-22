<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\GeneratorBundle\Tests\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\BundleGenerator;

class BundleGeneratorTest extends GeneratorTest
{
    public function testGenerateYaml()
    {
        $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'yml', false);

        $files = array(
            'FooBarBundle.php',
            'Controller/DefaultController.php',
            'Resources/views/Default/index.html.twig',
            'Resources/config/routing.yml',
            'Tests/Controller/DefaultControllerTest.php',
            'Resources/config/services.yml',
            'DependencyInjection/Configuration.php',
            'DependencyInjection/FooBarExtension.php',
        );
        foreach ($files as $file) {
            $this->assertTrue(file_exists($this->tmpDir.'/Foo/BarBundle/'.$file), sprintf('%s has been generated', $file));
        }

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/FooBarBundle.php');
        $this->assertContains('namespace Foo\\BarBundle', $content);

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/Controller/DefaultController.php');
        $this->assertContains('public function indexAction', $content);
        $this->assertNotContains('@Route("/hello/{name}"', $content);

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/Resources/views/Default/index.html.twig');
        $this->assertContains('Hello {{ name }}!', $content);
    }

    public function testGenerateAnnotation()
    {
        $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'annotation', false);

        $this->assertFalse(file_exists($this->tmpDir.'/Foo/BarBundle/Resources/config/routing.yml'));
        $this->assertFalse(file_exists($this->tmpDir.'/Foo/BarBundle/Resources/config/routing.xml'));

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/Controller/DefaultController.php');
        $this->assertContains('@Route("/hello/{name}"', $content);
    }

    public function testDirIsFile()
    {
        $this->filesystem->mkdir($this->tmpDir.'/Foo');
        $this->filesystem->touch($this->tmpDir.'/Foo/BarBundle');

        try {
            $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'yml', false);
            $this->fail('An exception was expected!');
        } catch (\RuntimeException $e) {
            $this->assertEquals(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.', realpath($this->tmpDir.'/Foo/BarBundle')), $e->getMessage());
        }
    }

    public function testIsNotWritableDir()
    {
        $this->filesystem->mkdir($this->tmpDir.'/Foo/BarBundle');
        $this->filesystem->chmod($this->tmpDir.'/Foo/BarBundle', 0444);

        try {
            $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'yml', false);
            $this->fail('An exception was expected!');
        } catch (\RuntimeException $e) {
            $this->filesystem->chmod($this->tmpDir.'/Foo/BarBundle', 0777);
            $this->assertEquals(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.', realpath($this->tmpDir.'/Foo/BarBundle')), $e->getMessage());
        }
    }

    public function testIsNotEmptyDir()
    {
        $this->filesystem->mkdir($this->tmpDir.'/Foo/BarBundle');
        $this->filesystem->touch($this->tmpDir.'/Foo/BarBundle/somefile');

        try {
            $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'yml', false);
            $this->fail('An exception was expected!');
        } catch (\RuntimeException $e) {
            $this->filesystem->chmod($this->tmpDir.'/Foo/BarBundle', 0777);
            $this->assertEquals(sprintf('Unable to generate the bundle as the target directory "%s" is not empty.', realpath($this->tmpDir.'/Foo/BarBundle')), $e->getMessage());
        }
    }

    public function testExistingEmptyDirIsFine()
    {
        $this->filesystem->mkdir($this->tmpDir.'/Foo/BarBundle');

        $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'yml', false);
    }

    protected function getGenerator()
    {
        $generator = new BundleGenerator($this->filesystem);
        $generator->setSkeletonDirs(__DIR__.'/../../Resources/skeleton');

        return $generator;
    }
}
