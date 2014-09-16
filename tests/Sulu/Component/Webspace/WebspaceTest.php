<?php

namespace Sulu\Component\Webspace;

use Prophecy\PhpUnit\ProphecyTestCase;

class WebspaceTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->webspace = new Webspace();

        $this->portal = $this->prophesize('Sulu\Component\Webspace\Portal');
        $this->localization = $this->prophesize('Sulu\Component\Webspace\Localization');
        $this->security = $this->prophesize('Sulu\Component\Webspace\Security');
        $this->segment = $this->prophesize('Sulu\Component\Webspace\Segment');
        $this->theme = $this->prophesize('Sulu\Component\Webspace\Theme');
    }

    public function testToArray()
    {
        $expected = array(
            'key' => 'foo',
            'name' => 'portal_key',
            'localizations' => array(
                array('fr'),
            ),
            'security' => array(
                'system' => 'sec_sys',
            ),
            'segments' => array(
                array(
                    'asd'
                )
            ),
            'portals' => array(
                array('one'),
            ),
            'theme' => array(
                'dsa',
            ),
            'navigation' => array(
                'contexts' => array()
            ),
        );

        $this->security->getSystem()->willReturn($expected['security']['system']);
        $this->localization->toArray()->willReturn($expected['localizations'][0]);
        $this->segment->toArray()->willReturn($expected['segments'][0]);
        $this->theme->toArray()->willReturn($expected['theme']);
        $this->portal->toArray()->willReturn($expected['portals'][0]);

        $this->webspace->setKey($expected['key']);
        $this->webspace->setName($expected['name']);
        $this->webspace->setLocalizations(array(
            $this->localization->reveal()
        ));
        $this->webspace->setSecurity($this->security->reveal());
        $this->webspace->setSegments(array(
            $this->segment->reveal()
        ));
        $this->webspace->setPortals(array(
            $this->portal->reveal()
        ));
        $this->webspace->setTheme($this->theme->reveal());

        $res = $this->webspace->toArray();
        $this->assertEquals($expected, $res);
    }
}
