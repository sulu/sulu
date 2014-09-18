<?php

namespace Sulu\Component\Theme;

use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Component\Webspace\Theme;

class ThemeTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->theme = new Theme();
    }

    public function testToArray()
    {
        $expected = array(
            'key' => 'foo',
            'excludedTemplates' => array('portal_key'),
        );

        $this->theme->setKey($expected['key']);
        $this->theme->setExcludedTemplates($expected['excludedTemplates']);

        $this->assertEquals($expected, $this->theme->toArray());
    }
}
