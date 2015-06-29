<?php

namespace Sulu\Component\Theme;

use Sulu\Component\Webspace\Theme;

class ThemeTest extends \PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
        $expected = array(
            'key' => 'foo',
            'errorTemplates' => array(),
        );

        $theme = new Theme();
        $theme->setKey($expected['key']);

        $this->assertEquals($expected, $theme->toArray());
    }

    public function testAddErrorTemplate()
    {
        $expected = array(
            'key' => 'foo',
            'defaultTemplates' => array('page' => 'default'),
            'errorTemplates' => array(),
        );
        $theme = new Theme();
        $theme->setKey($expected['key']);
        $theme->addErrorTemplate('404', 'template404');

        $this->assertEquals('template404', $theme->getErrorTemplate(404));
        $this->assertEquals($expected, $theme->toArray());
        $this->assertEquals($expected['errorTemplates'], $theme->getErrorTemplates());
    }

    public function testAddErrorTemplateDefault()
    {
        $expected = array(
            'key' => 'foo',
            'errorTemplates' => array('404' => 'template404', 'default' => 'template'),
        );
        $theme = new Theme();
        $theme->setKey($expected['key']);
        $theme->addErrorTemplate('default', 'template');
        $theme->addErrorTemplate('404', 'template404');

        $this->assertEquals('template404', $theme->getErrorTemplate(404));
        $this->assertEquals('template', $theme->getErrorTemplate(500));
        $this->assertEquals($expected, $theme->toArray());
        $this->assertEquals($expected['errorTemplates'], $theme->getErrorTemplates());
    }
}
