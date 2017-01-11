<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Exception\EnvironmentNotFoundException;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Url;

class PortalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Portal
     */
    private $portal;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Localization
     */
    private $localization;

    /**
     * @var Url
     */
    private $url;

    public function setUp()
    {
        parent::setUp();
        $this->portal = new Portal();
        $this->environment = $this->prophesize(Environment::class);
        $this->localization = $this->prophesize(Localization::class);
        $this->url = $this->prophesize(Url::class);
    }

    public function testGetEnvironment()
    {
        $this->environment->getType()->willReturn('dev');
        $this->portal->addEnvironment($this->environment->reveal());

        $this->assertEquals($this->environment->reveal(), $this->portal->getEnvironment('dev'));
    }

    public function testGetNotExistringEnvironment()
    {
        $this->setExpectedException(EnvironmentNotFoundException::class);

        $this->portal->getEnvironment('dev');
    }

    public function testGetEnvironmentFromEmptyPortal()
    {
        $this->setExpectedException(EnvironmentNotFoundException::class);
        $this->portal->getEnvironment('dev');
    }

    public function testToArray()
    {
        $expected = [
            'name' => 'foo',
            'key' => 'bar',
            'localizations' => [
                ['foo'],
            ],
            'environments' => [
                [
                    'type' => 'd',
                ],
            ],
        ];

        $this->environment->toArray()->willReturn($expected['environments'][0]);
        $this->environment->getType()->willReturn('d');
        $this->localization->toArray()->willReturn($expected['localizations'][0]);
        $this->localization->isDefault()->willReturn(true);
        $this->localization->isXDefault()->willReturn(false);
        $this->environment->getUrls()->willReturn([]);

        $this->portal->addEnvironment($this->environment->reveal());
        $this->portal->addLocalization($this->localization->reveal());
        $this->portal->setName($expected['name']);
        $this->portal->setKey($expected['key']);

        $this->assertEquals($expected, $this->portal->toArray());
    }
}
