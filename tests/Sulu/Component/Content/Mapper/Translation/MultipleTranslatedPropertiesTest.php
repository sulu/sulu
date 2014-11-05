<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper\Translation;

use Sulu\Component\Content\Structure;

class MultipleTranslatedPropertiesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MultipleTranslatedProperties
     */
    private $properties;

    public function setUp()
    {
        $this->properties = new MultipleTranslatedProperties(
            array(
                'template', 'foobar',
            ),
            'i18n'
        );
    }

    public function provideGetName()
    {
        return array(
            array(Structure::TYPE_PAGE, 'foobar', 'i18n:de-foobar'),
            array(Structure::TYPE_SNIPPET, 'template', 'template'),
            array(Structure::TYPE_PAGE, 'template', 'i18n:de-template'),
        );
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
