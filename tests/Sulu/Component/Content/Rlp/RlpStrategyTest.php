<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Rlp;

use ReflectionClass;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategy;

class RlpStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RlpStrategy
     */
    private $strategy;

    private $className = 'Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategy';

    protected function setUp()
    {
        $this->strategy = $this->getMockForAbstractClass($this->className);
    }

    protected function tearDown()
    {

    }

    private function getMethod($class, $name) {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testCleanUp()
    {
        $method = $this->getMethod($this->className, 'cleanup');
        $clean = $method->invokeArgs($this->strategy, array('/asdf asdf/äöü'));

        $this->assertEquals('/adsf-asdf/aeoeue', $clean);
    }
}
