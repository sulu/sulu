<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor;

class DoctrineCaseFieldDescriptorTest extends \PHPUnit_Framework_TestCase
{
    public function dataProvider()
    {
        return [
            [
                'test',
                new DoctrineDescriptor('entity1', 'field1'),
                new DoctrineDescriptor('entity2', 'field2'),
                'CASE WHEN entity1.field1 IS NOT NULL THEN entity1.field1 ELSE entity2.field2 END',
                [],
            ],
            [
                'test',
                new DoctrineDescriptor('test1', 'test2'),
                new DoctrineDescriptor('test3', 'test4'),
                'CASE WHEN test1.test2 IS NOT NULL THEN test1.test2 ELSE test3.test4 END',
                [],
            ],
            [
                'test',
                new DoctrineDescriptor(
                    'test1',
                    'test2',
                    [
                        'entity1' => new DoctrineJoinDescriptor(
                            'entity1',
                            'entity.relation'
                        ),
                    ]
                ),
                new DoctrineDescriptor('test3', 'test4'),
                'CASE WHEN test1.test2 IS NOT NULL THEN test1.test2 ELSE test3.test4 END',
                [
                    'entity1' => new DoctrineJoinDescriptor(
                        'entity1',
                        'entity.relation'
                    ),
                ],
            ],
            [
                'test',
                new DoctrineDescriptor(
                    'test1',
                    'test2'
                ),
                new DoctrineDescriptor(
                    'test3',
                    'test4',
                    [
                        'entity1' => new DoctrineJoinDescriptor(
                            'entity1',
                            'entity.relation'
                        ),
                    ]
                ),
                'CASE WHEN test1.test2 IS NOT NULL THEN test1.test2 ELSE test3.test4 END',
                [
                    'entity1' => new DoctrineJoinDescriptor(
                        'entity1',
                        'entity.relation'
                    ),
                ],
            ],
            [
                'test',
                new DoctrineDescriptor(
                    'test1',
                    'test2',
                    [
                        'test5' => new DoctrineJoinDescriptor(
                            'test6',
                            'test7'
                        ),
                    ]
                ),
                new DoctrineDescriptor(
                    'test3',
                    'test4',
                    [
                        'test8' => new DoctrineJoinDescriptor(
                            'test9',
                            'test10'
                        ),
                    ]
                ),
                'CASE WHEN test1.test2 IS NOT NULL THEN test1.test2 ELSE test3.test4 END',
                [
                    'test5' => new DoctrineJoinDescriptor(
                        'test6',
                        'test7'
                    ),
                    'test8' => new DoctrineJoinDescriptor(
                        'test9',
                        'test10'
                    ),
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testComplete($name, $case1, $case2, $select, $joins)
    {
        $fieldDescriptor = new DoctrineCaseFieldDescriptor($name, $case1, $case2);

        $this->assertEquals($select, $fieldDescriptor->getSelect());
        $this->assertEquals($joins, $fieldDescriptor->getJoins());
        $this->assertEquals($name, $fieldDescriptor->getName());
    }
}
