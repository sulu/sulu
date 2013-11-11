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
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategy;

class RlpStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RlpMapperInterface
     */
    private $mapper;
    /**
     * @var RlpStrategy
     */
    private $strategy;
    /**
     * @var string
     */
    private $className = 'Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategy';

    protected function setUp()
    {
        $this->mapper = $this->getMock(
            'Sulu\Component\Content\Types\Rlp\Mapper\RlpMapper',
            array('unique', 'getUniquePath', 'save'),
            array('test-mapper'),
            'TestMapper'
        );
        $this->mapper->expects($this->any())
            ->method('unique')
            ->will($this->returnCallback(array($this, 'uniqueCallback')));
        $this->mapper->expects($this->any())
            ->method('getUniquePath')
            ->will($this->returnCallback(array($this, 'getUniquePathCallback')));
        $this->mapper->expects($this->any())
            ->method('save')
            ->will($this->returnValue(1));

        $this->strategy = $this->getMockForAbstractClass(
            $this->className,
            array('test-strategy', $this->mapper),
            'TestStrategy'
        );
    }

    public function uniqueCallback()
    {
        $args = func_get_args();
        if ($args[0] == '/products/machines' || $args[0] == '/products/machines/drill') {
            return false;
        }

        return true;
    }

    public function getUniquePathCallback()
    {
        $args = func_get_args();
        if ($args[0] == '/products/machines' || $args[0] == '/products/machines/drill') {
            return $args[0] . '-1';
        }

        return $args[0];
    }

    protected function tearDown()
    {

    }

    private function getMethod($class, $name)
    {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function testCleanUp()
    {
        $method = $this->getMethod($this->className, 'cleanup');
        $clean = $method->invokeArgs($this->strategy, array('-/aSDf     asdf/äöü-'));

        $this->assertEquals('/asdf-asdf/aeoeue', $clean);
    }

    public function testIsValid()
    {
        // false from mapper
        $result = $this->strategy->isValid('/products/machines', 'default');
        $this->assertEquals(false, $result);

        // true from mapper
        $result = $this->strategy->isValid('/products/machines-1', 'default');
        $this->assertEquals(true, $result);

        // false from not good signs
        $result = $this->strategy->isValid('/products/mä chines', 'default');
        $this->assertEquals(false, $result);
    }

}
