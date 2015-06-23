<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

class PortalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Portal
     */
    private $portal;

    public function setUp()
    {
        parent::setUp();
        $this->portal = new Portal();
        $this->environment = $this->prophesize('Sulu\Component\Webspace\Environment');
        $this->localization = $this->prophesize('Sulu\Component\Localization\Localization');
        $this->url = $this->prophesize('Sulu\Component\Webspace\Url');
    }

    public function testGetEnvironment()
    {
        $this->environment->getType()->willReturn('dev');
        $this->portal->addEnvironment($this->environment->reveal());

        $this->assertEquals($this->environment->reveal(), $this->portal->getEnvironment('dev'));
    }

    public function testGetNotExistringEnvironment()
    {
        $this->setExpectedException('Sulu\Component\Webspace\Exception\EnvironmentNotFoundException');

        $this->portal->getEnvironment('dev');
    }

    public function testGetEnvironmentFromEmptyPortal()
    {
        $this->setExpectedException('Sulu\Component\Webspace\Exception\EnvironmentNotFoundException');
        $this->portal->getEnvironment('dev');
    }

    public function testToArray()
    {
        $expected = array(
            'name' => 'foo',
            'key' => 'bar',
            'resourceLocator' => array(
                'strategy' => 'hello',
            ),
            'localizations' => array(
                array('foo'),
            ),
            'environments' => array(
                array(
                    'type' => 'd',
                ),
            ),
        );

        $this->environment->toArray()->willReturn($expected['environments'][0]);
        $this->environment->getType()->willReturn('d');
        $this->localization->toArray()->willReturn($expected['localizations'][0]);
        $this->localization->isDefault()->willReturn(true);
        $this->environment->getUrls()->willReturn(array());

        $this->portal->addEnvironment($this->environment->reveal());
        $this->portal->addLocalization($this->localization->reveal());
        $this->portal->setResourceLocatorStrategy($expected['resourceLocator']['strategy']);
        $this->portal->setName($expected['name']);
        $this->portal->setKey($expected['key']);

        $this->assertEquals($expected, $this->portal->toArray());
    }
}
