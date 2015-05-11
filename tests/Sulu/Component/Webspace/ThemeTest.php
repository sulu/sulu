<?php

namespace Sulu\Component\Theme;

use Sulu\Component\Webspace\Theme;

class ThemeTest extends \PHPUnit_Framework_TestCase
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
            'errorTemplates'=> array(),
        );

        $this->theme->setKey($expected['key']);
        $this->theme->setExcludedTemplates($expected['excludedTemplates']);

        $this->assertEquals($expected, $this->theme->toArray());
    }
}
