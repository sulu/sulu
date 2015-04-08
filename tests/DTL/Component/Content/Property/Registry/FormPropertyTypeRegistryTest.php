<?php

namespace DTL\Component\Property\Type\Registry;

use Prophecy\PhpUnit\ProphecyTestCase;
use DTL\Component\Content\Property\Registry\FormPropertyTypeRegistry;

class FormPropertyTypeRegistryTest extends ProphecyTestCase
{
    private $formExtension;
    private $registry;

    public function setUp()
    {
        parent::setUp();
        $this->formExtension = $this->prophesize('Symfony\Component\Form\FormExtensionInterface');
        $this->contentType = $this->prophesize('DTL\Component\Content\Property\PropertyTypeInterface');
        $this->nonPropertyType = $this->prophesize('Symfony\Component\Form\FormTypeInterface');
        $this->registry = new FormPropertyTypeRegistry($this->formExtension->reveal());
    }

    /**
     * Ensure content type repository returns content types
     */
    public function testRegistry()
    {
        $this->formExtension->hasType('foo')->willReturn(true);
        $this->formExtension->getType('foo')->willReturn($this->contentType->reveal());
        $contentType = $this->registry->getProperty('foo');
        $this->assertSame($this->contentType->reveal(), $contentType);
    }
}
