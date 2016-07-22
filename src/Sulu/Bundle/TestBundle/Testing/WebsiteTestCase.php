<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

/**
 * WebTestCase is the base class for website functional tests.
 */
abstract class WebsiteTestCase extends BaseWebTestCase
{
    /**
     * Attempts to guess the WebsiteKernel location.
     *
     * When the WebsiteKernel is located, the file is required.
     *
     * @return string The WebsiteKernel class name
     *
     * @throws \RuntimeException
     */
    protected static function getKernelClass()
    {
        if (isset($_SERVER['KERNEL_DIR'])) {
            $dir = $_SERVER['KERNEL_DIR'];

            if (!is_dir($dir)) {
                $phpUnitDir = static::getPhpUnitXmlDir();
                if (is_dir("$phpUnitDir/$dir")) {
                    $dir = "$phpUnitDir/$dir";
                }
            }
        } else {
            $dir = static::getPhpUnitXmlDir();
        }

        $file = $dir . DIRECTORY_SEPARATOR . 'WebsiteKernel.php';

        if (!file_exists($file)) {
            throw new \RuntimeException('Either set KERNEL_DIR in your phpunit.xml according to https://symfony.com/doc/current/book/testing.html#your-first-functional-test or override the WebsiteTestCase::createKernel() method.');
        }

        require_once $file;

        return 'WebsiteKernel';
    }
}
