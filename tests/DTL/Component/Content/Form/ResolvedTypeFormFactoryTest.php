<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Form;

use DTL\Component\Content\Form\ResolvedFormTypeFactory;
use Prophecy\PhpUnit\ProphecyTestCase;

class ResolvedTypeFormFactoryTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->factory = new ResolvedFormTypeFactory();
        $this->formType = $this->prophesize('Symfony\Component\Form\FormTypeInterface');
        $this->contentType = $this->prophesize('DTL\Component\Content\Form\ContentTypeInterface');
        $this->parent = $this->prophesize('Symfony\Component\Form\ResolvedFormTypeInterface');
    }

    public function testFactoryContentType()
    {
        $result = $this->factory->createResolvedType($this->contentType->reveal(), array(), $this->parent->reveal());
        $this->assertInstanceOf('DTL\Component\Content\Form\ContentResolvedTypeInterface', $result);
    }
}
