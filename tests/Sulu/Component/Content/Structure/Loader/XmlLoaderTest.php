<?php

namespace Sulu\Component\Content\Metadata\Loader;

use Sulu\Component\Content\Metadata\Loader\XmlLoader;

class XmlLoaderTest extends \PHPUnit_Framework_TestCase
{
    private $loader;

    public function setUp()
    {
        $this->loader = new XmlLoader();
    }

    public function testLoadTemplate()
    {
        $result = $this->load('template.xml');
    }

    private function load($name)
    {
        $result = $this->loader->load(
            __DIR__ . '/../../../../../Resources/DataFixtures/Page/' . $name
        );

        return $result;
    }
}
