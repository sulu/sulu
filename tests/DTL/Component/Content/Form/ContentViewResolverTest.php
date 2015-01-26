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
use DTL\Component\Content\Form\ContentFormLoaderInterface;
use Symfony\Component\Form\Test\FormInterface;

class ContentViewResolverTest extends ProphecyTestCase
{
    /**
     * @var ContentView
     */
    private $resolver;

    /**
     * @var ContentFormLoaderInterface
     */
    private $loader;

    /**
     * @var FormInterface
     */
    private $contentForm;

    public function setUp()
    {
        parent::setUp();

        $this->loader = $this->prophesize('DTL\Component\Content\Form\ContentFormLoaderInterface');
        $this->document1 = $this->prophesize('DTL\Bundle\ContentBundle\Document\StructureDocument');
        $this->contentForm = $this->prophesize('Symfony\Component\Form\FormInterface');
        $this->contentType1 = $this->prophesize('Symfony\Component\Form\FormTypeInterface');
        $this->contentType2 = $this->prophesize('DTL\Component\Content\Form\ContentTypeInterface');

        $this->resolver = new ContentViewResolver($this->loader);
    }

    public function provideResolve()
    {
        return array(
            array(
                'my_form',
            ),
        );
    }

    /**
     * @dataProvider provideResolve
     */
    public function testResolve($formName)
    {
        $this->loader->load($formName)->willReturn($this->contentForm);
        $this->resolver->resolve($this->document1);
    }
}
