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

use DTL\Component\Content\Form\ContentTypeInterface;
use Symfony\Component\Form\FormInterface;
use DTL\Component\Content\Form\ContentView;
use Prophecy\PhpUnit\ProphecyTestCase;

class ContentResolvedTypeTest extends ProphecyTestCase
{
    /**
     * @var ContentTypeInterface
     */
    private $innerType;

    /**
     * @var ContentResolvedType
     */
    private $resolvedType;

    /**
     * @var ContentView
     */
    private $view;

    /**
     * @var FormInterface
     */
    private $form;

    public function setUp()
    {
        parent::setUp();
        $this->innerType = $this->prophesize('DTL\Component\Content\Form\ContentTypeInterface');
        $this->view = $this->prophesize('DTL\Component\Content\Form\ContentView');
        $this->form = $this->prophesize('Symfony\Component\Form\FormInterface');
        $this->resolvedType = new ContentResolvedType($this->innerType->reveal());
    }

    public function testBuildContentView()
    {
        $this->innerType->buildContentView($this->view->reveal(), $this->form->reveal())->shouldBeCalled();
        $this->resolvedType->buildContentView($this->view->reveal(), $this->form->reveal());
    }
}
