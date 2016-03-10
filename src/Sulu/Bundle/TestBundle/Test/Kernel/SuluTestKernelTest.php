<?php

namespace Sulu\Bundle\TestBundle\Test\Kernel;

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;

class SuluTestKernelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * It should generate webspaces.
     */
    public function testGenerateWebspaces()
    {
        SuluTestKernel::generateWebspaces([
            [ 
                'key' => 'sulu_io',
                'localizations' => [ 'de' => []],
                'navigation' => [
                    'main' => [],
                ],
                'portals' => [
                    'test' => []
                ],
            ],
            [ 
                'key' => 'test_io',
                'localizations' => [ 'de' => [], 'fr' => []],
                'navigation' => [
                    'main' => [],
                    'footer' => [],
                ],
                'portals' => [
                    'test' => []
                ],
            ],
        ]);

        $this->assertFileExists(__DIR__ . '/../../Resources/webspaces/sulu_io.xml');
        $this->assertFileExists(__DIR__ . '/../../Resources/webspaces/test_io.xml');

        SuluTestKernel::generateWebspaces([
            [ 
                'key' => 'sulu_io',
            ]
        ]);

        $this->assertFileNotExists(__DIR__ . '/../../Resources/webspaces/test_io.xml');
    }
}
