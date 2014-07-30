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

class PortalInformationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PortalInformation
     */
    private $portalInformation;

    public function setUp()
    {
        $this->portalInformation = new PortalInformation(null, null, null, null, null);
    }

    public function provideUrlHosts()
    {
        return array(
            array('sulu.lo', 'sulu.lo'),
            array('sulu.io/', 'sulu.io'),
            array('sulu.com/example', 'sulu.com')
        );
    }

    /**
     * @dataProvider provideUrlHosts
     */
    public function testGetHost($url, $host)
    {
        $this->portalInformation->setUrl($url);

        $this->assertEquals($host, $this->portalInformation->getHost());
    }
}
