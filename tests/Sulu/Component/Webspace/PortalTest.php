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
        $this->portal = new Portal();
    }

    public function testGetEnvironment()
    {
        $environment = new Environment();
        $environment->setType('dev');

        $this->portal->addEnvironment($environment);

        $this->assertEquals($environment, $this->portal->getEnvironment('dev'));
    }

    public function testGetNotExistringEnvironment()
    {
        $environment = new Environment();
        $environment->setType('prod');

        $this->portal->addEnvironment($environment);

        $this->setExpectedException('Sulu\Component\Webspace\Exception\EnvironmentNotFoundException');

        $this->portal->getEnvironment('dev');
    }

    public function testGetEnvironmentFromEmptyPortal()
    {
        $this->setExpectedException('Sulu\Component\Webspace\Exception\EnvironmentNotFoundException');

        $this->portal->getEnvironment('dev');
    }
}
