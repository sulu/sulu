<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DistributionBundle\Tests\Unit\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Sulu\Bundle\DistributionBundle\Composer\ScriptHandler;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $tempDir;

    private $resourceDir;

    private $previousWd;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Composer
     */
    private $composer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|IOInterface
     */
    private $io;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RootPackageInterface
     */
    private $rootPackage;

    /**
     * @var Event
     */
    private $event;

    protected function setUp()
    {
        $this->tempDir = self::makeTempDir(__CLASS__);
        $this->previousWd = getcwd();
        $this->composer = $this->getMockBuilder('Composer\Composer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->io = $this->getMock('Composer\IO\IOInterface');
        $this->rootPackage = $this->getMock('Composer\Package\RootPackageInterface');
        $this->event = new Event(ScriptEvents::POST_INSTALL_CMD, $this->composer, $this->io);

        $this->composer->expects($this->any())
            ->method('getPackage')
            ->willReturn($this->rootPackage);

        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__ . '/Fixtures/app', $this->tempDir . '/app');

        chdir($this->tempDir);
    }

    protected function tearDown()
    {
        // Change back, otherwise deleting fails on Windows
        chdir($this->previousWd);

        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    public function testCopyDistFilesIfNotPresent()
    {
        $this->rootPackage->expects($this->any())
            ->method('getExtra')
            ->willReturn([]);

        ScriptHandler::installDistFiles($this->event);

        $this->assertFileExists($this->tempDir . '/app/Resources/pages/default.xml');
        $this->assertFileExists($this->tempDir . '/app/Resources/pages/overview.xml');
        $this->assertFileExists($this->tempDir . '/app/Resources/snippets/default.xml');
        $this->assertFileExists($this->tempDir . '/app/Resources/webspaces/sulu.io.xml');

        $this->assertFileEquals(
            $this->tempDir . '/app/Resources/pages/default.xml',
            $this->tempDir . '/app/Resources/pages/default.xml.dist'
        );
        $this->assertFileEquals(
            $this->tempDir . '/app/Resources/pages/overview.xml',
            $this->tempDir . '/app/Resources/pages/overview.xml.dist'
        );
        $this->assertFileEquals(
            $this->tempDir . '/app/Resources/snippets/default.xml',
            $this->tempDir . '/app/Resources/snippets/default.xml.dist'
        );
        $this->assertFileEquals(
            $this->tempDir . '/app/Resources/webspaces/sulu.io.xml',
            $this->tempDir . '/app/Resources/webspaces/sulu.io.xml.dist'
        );
    }

    public function testKeepExistingFiles()
    {
        $this->rootPackage->expects($this->any())
            ->method('getExtra')
            ->willReturn([]);

        file_put_contents($this->tempDir . '/app/Resources/pages/overview.xml', 'custom overview');
        file_put_contents($this->tempDir . '/app/Resources/webspaces/sulu.io.xml', 'custom webspaces');

        ScriptHandler::installDistFiles($this->event);

        $this->assertFileExists($this->tempDir . '/app/Resources/pages/default.xml');
        $this->assertFileExists($this->tempDir . '/app/Resources/pages/overview.xml');
        $this->assertFileExists($this->tempDir . '/app/Resources/snippets/default.xml');
        $this->assertFileExists($this->tempDir . '/app/Resources/webspaces/sulu.io.xml');

        $this->assertFileEquals(
            $this->tempDir . '/app/Resources/pages/default.xml',
            $this->tempDir . '/app/Resources/pages/default.xml.dist'
        );
        $this->assertFileEquals(
            $this->tempDir . '/app/Resources/snippets/default.xml',
            $this->tempDir . '/app/Resources/snippets/default.xml.dist'
        );

        $this->assertEquals('custom overview', file_get_contents($this->tempDir . '/app/Resources/pages/overview.xml'));
        $this->assertEquals('custom webspaces', file_get_contents($this->tempDir . '/app/Resources/webspaces/sulu.io.xml'));
    }

    public function testCopyDistFilesToCustomAppDirectoryIfPresent()
    {
        $this->rootPackage->expects($this->any())
            ->method('getExtra')
            ->willReturn([
                'symfony-app-dir' => 'foobar',
            ]);

        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__ . '/Fixtures/app', $this->tempDir . '/foobar');

        ScriptHandler::installDistFiles($this->event);

        $this->assertFileExists($this->tempDir . '/foobar/Resources/pages/default.xml');
        $this->assertFileExists($this->tempDir . '/foobar/Resources/pages/overview.xml');
        $this->assertFileExists($this->tempDir . '/foobar/Resources/snippets/default.xml');
        $this->assertFileExists($this->tempDir . '/foobar/Resources/webspaces/sulu.io.xml');
    }

    public function testDoNotCopyDistFilesIfDisabled()
    {
        $this->rootPackage->expects($this->any())
            ->method('getExtra')
            ->willReturn([
                'sulu-dist-install' => false,
            ]);

        ScriptHandler::installDistFiles($this->event);

        $this->assertFileNotExists($this->tempDir . '/app/Resources/pages/default.xml');
        $this->assertFileNotExists($this->tempDir . '/app/Resources/pages/overview.xml');
        $this->assertFileNotExists($this->tempDir . '/app/Resources/snippets/default.xml');
        $this->assertFileNotExists($this->tempDir . '/app/Resources/webspaces/sulu.io.xml');
    }

    public static function makeTempDir($className)
    {
        if (false !== ($pos = strrpos($className, '\\'))) {
            $namespace = substr($className, strpos($className, '/')) . '/';
            $shortClass = substr($className, $pos + 1);
        } else {
            $namespace = '/';
            $shortClass = $className;
        }

        // Usage of realpath() is important if the temporary directory is a
        // symlink to another directory (e.g. /var => /private/var on some Macs)
        // We want to know the real path to avoid comparison failures with
        // code that uses real paths only
        $systemTempDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(sys_get_temp_dir()));
        $basePath = $systemTempDir . '/' . $namespace . '/' . $shortClass;

        while (false === @mkdir($tempDir = $basePath . rand(10000, 99999), 0777, true)) {
            // Run until we are able to create a directory
        }

        return $tempDir;
    }
}
