<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatLoader;



class XmlFormatLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testValid()
    {
        $fileLocator = $this->getMockBuilder('Symfony\Component\Config\FileLocator')
            ->disableOriginalConstructor()
            ->getMock();

        $fileLocator->expects($this->once())
            ->method('locate')
            ->willReturnArgument(0);

        $loader = new XmlFormatLoader($fileLocator);

        $result = $loader->load(dirname(__DIR__) . '/../../Fixtures/image/formats/valid.xml');
        $this->assertEquals(
            array(
                '640x480' => array(
                    'name' => '640x480',
                    'commands' => array(
                        array(
                            'action' => 'scale',
                            'parameters' => array(
                                'x' => 640,
                                'y' => 480,
                                'forceRatio' => true,
                            ),
                        ),
                    ),
                    'options' => array(
                        'jpeg_quality' => 70,
                        'png_compression_level' => 6,
                    ),
                ),
                '300x300' => array(
                    'name' => '300x300',
                    'commands' => array(
                        array(
                            'action' => 'resize',
                            'parameters' => array(
                                'x' => 300,
                                'y' => 300,
                            ),
                        ),
                    ),
                    'options' => array(
                        'png_compression_level' => 3,
                    ),
                ),
            ),
            $result
        );
    }

    public function testValidNoOptions()
    {
        $fileLocator = $this->getMockBuilder('Symfony\Component\Config\FileLocator')
            ->disableOriginalConstructor()
            ->getMock();

        $fileLocator->expects($this->once())
            ->method('locate')
            ->willReturnArgument(0);

        $loader = new XmlFormatLoader($fileLocator);

        $result = $loader->load(dirname(__DIR__) . '/../../Fixtures/image/formats/valid_no_options.xml');
        $this->assertEquals(
            array(
                '640x480' => array(
                    'name' => '640x480',
                    'commands' => array(
                        array(
                            'action' => 'scale',
                            'parameters' => array(
                                'x' => 640,
                                'y' => 480,
                                'forceRatio' => false,
                            ),
                        ),
                    ),
                    'options' => array(),
                ),
            ),
            $result
        );
    }

    public function testValidNoCommands()
    {
        $fileLocator = $this->getMockBuilder('Symfony\Component\Config\FileLocator')
            ->disableOriginalConstructor()
            ->getMock();

        $fileLocator->expects($this->once())
            ->method('locate')
            ->willReturnArgument(0);

        $loader = new XmlFormatLoader($fileLocator);

        $result = $loader->load(dirname(__DIR__) . '/../../Fixtures/image/formats/valid_no_commands.xml');
        $this->assertEquals(
            array(
                '640x480' => array(
                    'name' => '640x480',
                    'commands' => array(),
                    'options' => array(
                        'jpeg_quality' => 70,
                        'png_compression_level' => 6,
                    ),
                ),
            ),
            $result
        );
    }

    public function testDefaultValues()
    {
        $fileLocator = $this->getMockBuilder('Symfony\Component\Config\FileLocator')
            ->disableOriginalConstructor()
            ->getMock();

        $fileLocator->expects($this->once())
            ->method('locate')
            ->willReturnArgument(0);

        $loader = new XmlFormatLoader($fileLocator);
        $loader->setDefaultOptions(array('jpeg_quality' => 10, 'a' => 'test'));

        $result = $loader->load(dirname(__DIR__) . '/../../Fixtures/image/formats/valid.xml');
        $this->assertEquals(
            array(
                '640x480' => array(
                    'name' => '640x480',
                    'commands' => array(
                        array(
                            'action' => 'scale',
                            'parameters' => array(
                                'x' => 640,
                                'y' => 480,
                                'forceRatio' => true,
                            ),
                        ),
                    ),
                    'options' => array(
                        'jpeg_quality' => 70,
                        'png_compression_level' => 6,
                        'a' => 'test',
                    ),
                ),
                '300x300' => array(
                    'name' => '300x300',
                    'commands' => array(
                        array(
                            'action' => 'resize',
                            'parameters' => array(
                                'x' => 300,
                                'y' => 300,
                            ),
                        ),
                    ),
                    'options' => array(
                        'png_compression_level' => 3,
                        'jpeg_quality' => 10,
                        'a' => 'test',
                    ),
                ),
            ),
            $result
        );
    }
}
