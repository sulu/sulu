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

<<<<<<< HEAD
        $this->theme->setKey($expected['key']);
        $this->theme->setExcludedTemplates($expected['excludedTemplates']);
        $this->theme->addDefaultTemplate('page', 'default');
=======
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
>>>>>>> removed excluded from webspace theme

        $this->assertEquals('template404', $theme->getErrorTemplate(404));
        $this->assertEquals('template', $theme->getErrorTemplate(500));
        $this->assertEquals($expected, $theme->toArray());
        $this->assertEquals($expected['errorTemplates'], $theme->getErrorTemplates());
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
