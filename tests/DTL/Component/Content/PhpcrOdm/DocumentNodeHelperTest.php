<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\PhpcrOdm;

use Prophecy\PhpUnit\ProphecyTestCase;
use DTL\Component\Content\PhpcrOdm\Serializer\PropertyNameEncoder;

class DocumentNameHelperTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->namespaceRegistry = $this->prophesize('DTL\Component\Content\PhpcrOdm\NamespaceRoleRegistry');
        $this->helper = new DocumentNodeHelper(
            $this->namespaceRegistry->reveal()
        );

        $this->namespaceRegistry->getAlias('localized-content')->willReturn('lsys');
        $this->namespaceRegistry->getAlias('localized-system')->willReturn('lsys');
        $this->namespaceRegistry->getAlias('content')->willReturn('ncon');
    }

    public function testEncodeLocalized()
    {
        $res = $this->helper->encodeLocalizedContentName('prop', 'de');
        $this->assertEquals('lsys:de-prop', $res);
    }

    public function testEncode()
    {
        $res = $this->helper->encodeContentName('prop');
        $this->assertEquals('ncon:prop', $res);
    }

    public function provideGetLocales()
    {
        return array(
            array(
                array(
                    'lsys:de-foobar',
                    'lsys:de-title',
                    'lsys:fr-title',
                    'lsys:de_at-title',
                    'lsys:fr-barfoo',
                ),
                array(
                    'de', 'fr', 'de_at',
                ),
            ),
            array(
                array(
                    'lsys:de-title',
                    'lsys:de-barbar',
                    'lsys:fr-barbar',
                    'lsys:fr-barfoo',
                ),
                array(
                    'de'
                ),
            ),
            array(
                array(
                ),
                array(
                ),
            ),
        );
    }

    /**
     * @dataProvider provideGetLocales
     */
    public function testGetLocales($propertyNames, array $expectedLocales)
    {
        $this->node = $this->prophesize('PHPCR\NodeInterface');
        $properties = array();

        foreach ($propertyNames as $propertyName) {
            $property = $this->prophesize('PHPCR\PropertyInterface');
            $property->getName()->willReturn($propertyName);
            $properties[$propertyName] = $property->reveal();
        }

        $this->node->getProperties('lsys:*')->willReturn($properties);

        $locales = $this->helper->getLocales(
            $this->node->reveal()
        );

        $this->assertEquals($expectedLocales, $locales);
    }
}

