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
            'defaultTemplates' => array('page' => 'default'),
            'excludedTemplates' => array('portal_key'),
            'errorTemplates' => array(),
        );

        $this->theme->setKey($expected['key']);
        $this->theme->setExcludedTemplates($expected['excludedTemplates']);
        $this->theme->addDefaultTemplate('page', 'default');

        $this->assertEquals($expected, $this->theme->toArray());
    }

    public function testAddErrorTemplate()
    {
        $expected = array(
            'key' => 'foo',
            'excludedTemplates' => array(),
            'errorTemplates' => array(),
            'defaultTemplates' => array('page' => 'default', 'homepage' => 'overview'),
        );

        $theme = new Theme();
        $theme->setKey($expected['key']);
        $theme->addDefaultTemplate('page', 'default');
        $theme->addDefaultTemplate('homepage', 'overview');

        $this->assertEquals($expected['defaultTemplates'], $theme->getDefaultTemplates());
        $this->assertEquals($expected['defaultTemplates']['page'], $theme->getDefaultTemplate('page'));
        $this->assertEquals($expected['defaultTemplates']['homepage'], $theme->getDefaultTemplate('homepage'));
        $this->assertNull($theme->getDefaultTemplate('other-type'));

        $this->assertEquals($expected, $theme->toArray());
    }
}
