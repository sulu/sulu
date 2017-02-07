<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Mapper\Translation;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;

class MultipleTranslatedPropertiesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MultipleTranslatedProperties
     */
    private $properties;

    public function setUp()
    {
        $this->properties = new MultipleTranslatedProperties(
            [
                'template', 'foobar',
            ],
            'i18n'
        );
    }

    public function provideGetName()
    {
        return [
            [Structure::TYPE_PAGE, 'foobar', 'i18n:de-foobar'],
            [Structure::TYPE_SNIPPET, 'template', 'template'],
            [Structure::TYPE_PAGE, 'template', 'i18n:de-template'],
        ];
    }

    /**
     * @dataProvider provideGetName
     */
    public function testGetName($structureType, $name, $expectedName)
    {
        $this->properties->setLanguage('de');
        $this->properties->setStructureType($structureType);
        $res = $this->properties->getName($name);
        $this->assertEquals($expectedName, $res);
    }
}
