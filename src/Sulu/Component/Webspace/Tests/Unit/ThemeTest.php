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

use Sulu\Component\Webspace\Theme;

class ThemeTest extends \PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
        $expected = [
            'key' => 'foo',
            'defaultTemplates' => ['page' => 'default'],
            'errorTemplates' => ['404' => 'template404'],
        ];

        $theme = new Theme();
        $theme->setKey($expected['key']);
        $theme->addDefaultTemplate('page', 'default');
        $theme->addErrorTemplate('404', 'template404');

        $this->assertEquals($expected, $theme->toArray());
    }

    public function testAddErrorTemplate()
    {
        $expected = [
            'key' => 'foo',
            'defaultTemplates' => [],
            'errorTemplates' => ['404' => 'template404'],
        ];
        $theme = new Theme();
        $theme->setKey($expected['key']);
        $theme->addErrorTemplate('404', 'template404');

        $this->assertEquals('template404', $theme->getErrorTemplate(404));
        $this->assertEquals($expected, $theme->toArray());
        $this->assertEquals($expected['errorTemplates'], $theme->getErrorTemplates());
    }

    public function testAddErrorTemplateDefault()
    {
        $expected = [
            'key' => 'foo',
            'defaultTemplates' => [],
            'errorTemplates' => ['404' => 'template404', 'default' => 'template'],
        ];
        $theme = new Theme();
        $theme->setKey($expected['key']);
        $theme->addErrorTemplate('default', 'template');
        $theme->addErrorTemplate('404', 'template404');

        $this->assertEquals('template404', $theme->getErrorTemplate(404));
        $this->assertEquals('template', $theme->getErrorTemplate(500));
        $this->assertEquals($expected, $theme->toArray());
        $this->assertEquals($expected['errorTemplates'], $theme->getErrorTemplates());
    }

    public function testAddDefaultTemplate()
    {
        $expected = [
            'key' => 'foo',
            'errorTemplates' => [],
            'defaultTemplates' => ['page' => 'default', 'homepage' => 'overview'],
        ];
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
