<?php

namespace Sulu\Bundle\ContactBundle\Tests\Unit;

use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Util\IdsHandlingTrait;

class IdsHandlingTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param mixed $object
     * @param string $method
     *
     * @return \ReflectionMethod
     */
    private function getPrivateMethod($object, $method)
    {
        $class = new \ReflectionClass($object);
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }

    public function parseIdsProvider()
    {
        return [
            [[], [], []],
            [[], ['a' => [], 'c' => []], ['a' => [], 'c' => []]],
            [['a1', 'c1', 'c3', 'a15'], ['a' => [], 'c' => []], ['a' => [1, 15], 'c' => [1, 3]]],
            [
                ['a1', 'c1', 'c3', 'a15', 'b5'],
                ['a' => [], 'c' => [], 'd' => []],
                ['a' => [1, 15], 'b' => [5], 'c' => [1, 3], 'd' => []],
            ],
        ];
    }

    /**
     * @dataProvider parseIdsProvider
     *
     * @param array $values
     * @param array $default
     * @param array $expected
     */
    public function testParseIds($values, $default, $expected)
    {
        $object = $this->getObjectForTrait(IdsHandlingTrait::class);
        $method = $this->getPrivateMethod($object, 'parseIds');

        $result = $method->invokeArgs($object, [$values, $default]);

        $this->assertEquals($expected, $result);
    }

    private function getObject($id, $class)
    {
        $mock = $this->prophesize($class);
        $mock->getId()->willReturn($id);

        return $mock->reveal();
    }

    public function sortByIdsProvider()
    {
        $objects = [
            $this->getObject('c1', Contact::class),
            $this->getObject('a1', Account::class),
            $this->getObject('c2', Contact::class),
            $this->getObject('a2', Account::class),
        ];

        return [
            [[], [], []],
            [
                [],
                [
                    ['id' => 'c3', 'name' => 'test'],
                    ['id' => 'c1', 'name' => 'test'],
                    ['id' => 'a1', 'name' => 'test'],
                ],
                [],
            ],
            [
                ['a1'],
                [
                    ['id' => 'c3', 'name' => 'test'],
                    ['id' => 'c1', 'name' => 'test'],
                    ['id' => 'a1', 'name' => 'test'],
                ],
                [
                    ['id' => 'a1', 'name' => 'test'],
                ],
            ],
            [
                ['a1', 'c1', 'c3'],
                [
                    ['id' => 'c3', 'name' => 'test'],
                    ['id' => 'c1', 'name' => 'test'],
                    ['id' => 'a1', 'name' => 'test'],
                ],
                [
                    ['id' => 'a1', 'name' => 'test'],
                    ['id' => 'c1', 'name' => 'test'],
                    ['id' => 'c3', 'name' => 'test'],
                ],
            ],
            [
                ['1', '2', '3'],
                [
                    ['id' => '3', 'name' => 'test'],
                    ['id' => '2', 'name' => 'test'],
                    ['id' => '1', 'name' => 'test'],
                ],
                [
                    ['id' => '1', 'name' => 'test'],
                    ['id' => '2', 'name' => 'test'],
                    ['id' => '3', 'name' => 'test'],
                ],
            ],
            [
                ['3', '1', '2'],
                [
                    ['id' => '3', 'name' => 'test'],
                    ['id' => '2', 'name' => 'test'],
                    ['id' => '1', 'name' => 'test'],
                ],
                [
                    ['id' => '3', 'name' => 'test'],
                    ['id' => '1', 'name' => 'test'],
                    ['id' => '2', 'name' => 'test'],
                ],
            ],
            [
                ['c1', 'a1', 'c2', 'a2'],
                [$objects[3], $objects[2], $objects[1], $objects[0]],
                $objects,
            ],
            [
                ['c1', 'a1', 'c2'],
                [$objects[3], $objects[2], $objects[1], $objects[0]],
                array_slice($objects, 0, 3),
            ],
            [
                [],
                [$objects[3], $objects[2], $objects[1], $objects[0]],
                [],
            ],
            [
                [],
                [],
                [],
            ],
        ];
    }

    /**
     * @dataProvider sortByIdsProvider
     *
     * @param array $values
     * @param array $list
     * @param array $expected
     */
    public function testSortByIds($values, $list, $expected)
    {
        $object = $this->getObjectForTrait(IdsHandlingTrait::class);
        $method = $this->getPrivateMethod($object, 'sortByIds');

        $result = $method->invokeArgs($object, [$values, $list]);

        $this->assertEquals($expected, $result);
    }

    public function sortEntitiesByIdsProvider()
    {
        $objects = [
            $this->getObject('1', Contact::class),
            $this->getObject('1', Account::class),
            $this->getObject('2', Contact::class),
            $this->getObject('2', Account::class),
        ];

        $typeFunction = function ($entity) {
            if ($entity instanceof Contact) {
                return 'c';
            } elseif ($entity instanceof Account) {
                return 'a';
            }

            return '';
        };

        return [
            [
                ['c1', 'a1', 'c2', 'a2'],
                [$objects[3], $objects[2], $objects[1], $objects[0]],
                $typeFunction,
                $objects,
            ],
            [
                ['c1', 'a1', 'c2'],
                [$objects[3], $objects[2], $objects[1], $objects[0]],
                $typeFunction,
                array_slice($objects, 0, 3),
            ],
            [
                [],
                [$objects[3], $objects[2], $objects[1], $objects[0]],
                $typeFunction,
                [],
            ],
            [
                [],
                [],
                $typeFunction,
                [],
            ],
        ];
    }

    /**
     * @dataProvider sortEntitiesByIdsProvider
     *
     * @param array $values
     * @param array $list
     * @param callable $typeFunction
     * @param array $expected
     */
    public function testSortEntitiesByIds($values, $list, $typeFunction, $expected)
    {
        $object = $this->getObjectForTrait(IdsHandlingTrait::class);
        $method = $this->getPrivateMethod($object, 'sortEntitiesByIds');

        $result = $method->invokeArgs($object, [$values, $list, $typeFunction]);

        $this->assertEquals($expected, $result);
    }
}
