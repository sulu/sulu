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

use Prophecy\PhpUnit\ProphecyTestCase;
use DTL\Component\Content\Form\ContentViewResolver;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Prophecy\Argument;

class ContentViewResolverTest extends ProphecyTestCase
{
    /**
     * @var ContentView
     */
    private $resolver;

    /**
     * @var ContentFormFactoryInterface
     */
    private $factory;

    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var FormTypeInterface[]
     */
    private $contentTypes;

    private $formChildrenProphets = array();

    public function setUp()
    {
        parent::setUp();

        $this->factory = $this->prophesize('Symfony\Component\Form\FormFactoryInterface');
        $this->document1 = $this->prophesize('DTL\Bundle\ContentBundle\Document\FormDocument');
        $this->form = $this->prophesize('Symfony\Component\Form\FormInterface');

        $children = array();
        $prodigies = array();

        foreach (array('one', 'two') as $childName) {
            $this->contentTypes[$childName] = $this->prophesize('DTL\Component\Content\Form\ContentResolvedTypeInterface');

            $formConfig = $this->prophesize('Symfony\Component\Form\FormConfigInterface');
            $formConfig->getType()->willReturn($this->contentTypes[$childName]->reveal());

            $prodigies[$childName] = $this->prophesize('Symfony\Component\Form\Test\FormInterface');
            $prodigies[$childName]->getConfig()->willReturn($formConfig);

            $children[$childName] = $prodigies[$childName]->reveal();
        }

        $this->formChildrenProphets = $prodigies;
        $this->formChildren = $children;

        $this->resolver = new ContentViewResolver($this->factory->reveal());
    }

    public function provideResolve()
    {
        return array(
            array(
                'my_form',
                array(
                    'one' => 'data',
                    'two' => 'data',
                ),
            ),
        );
    }

    /**
     * @dataProvider provideResolve
     */
    public function testResolve($formName, $data)
    {
        $this->document1->getFormType()->willReturn($formName);
        $this->document1->getContentData()->willReturn($data);
        $this->factory->create($formName)->willReturn($this->form);
        $this->form->setData($data)->shouldBeCalled();
        $this->form->all()->willReturn($this->formChildren);

        foreach ($data as $key => $value) {
            $this->formChildrenProphets[$key]->getData()->willReturn($value);
            $this->contentTypes[$key]->buildContentView(
                Argument::type('DTL\Component\Content\Form\ContentView'),
                Argument::type('Symfony\Component\Form\Test\FormInterface')
            )->shouldBeCalled();
        }

        $contentView = $this->resolver->resolve($this->document1->reveal());

        $this->assertInstanceOf('DTL\Component\Content\Form\ContentView', $contentView);
    }
}
