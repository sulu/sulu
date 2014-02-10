<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types;

use Sulu\Bundle\ContentBundle\Content\Types\SmartContent;

//FIXME remove on update to phpunit 3.8, caused by https://github.com/sebastianbergmann/phpunit/issues/604
interface NodeInterface extends \PHPCR\NodeInterface, \Iterator
{
}

class SmartContentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SmartContent
     */
    private $smartContent;

    public function setUp()
    {
        $this->smartContent = new SmartContent('SuluContentBundle:Template:content-types/smart_content.html.twig');
    }

    public function testTemplate()
    {
        $this->assertEquals(
            'SuluContentBundle:Template:content-types/smart_content.html.twig',
            $this->smartContent->getTemplate()
        );
    }

    public function testSet()
    {
        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true,
            array('setProperty')
        );
        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\PropertyInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getValue')
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                array(
                    'dataSource' => array(
                        'home/products'
                    ),
                    'sortBy' => array(
                        'published'
                    )
                )
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                array(
                    'dataSource' => array(
                        'home/products'
                    ),
                    'sortBy' => array(
                        'published'
                    )
                )
            )
        );

        $this->smartContent->set($node, $property, 'test');
    }
}
