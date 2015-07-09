<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit;

use Jackalope\Node;
use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Content\Types\ContactSelectionContentType;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeInterface;

class ContactSelectionContentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $template = '@TestBundle:Templates:my-template.html.twig';

    /**
     * @var string
     */
    private $webspaceKey = 'sulu_test';

    /**
     * @var string
     */
    private $locale = 'de';

    /**
     * @var int
     */
    private $userId = 1;

    /**
     * @var string
     */
    private $segmentKey = 'winter';

    /**
     * @var ContactManagerInterface
     */
    private $contactManager;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var StructureInterface
     */
    private $structure;

    protected function setUp()
    {
        parent::setUp();

        $this->contactManager = $this->prophesize(ContactManagerInterface::class);
        $this->node = $this->prophesize(Node::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->structure = $this->prophesize(StructureInterface::class);

        $this->structure->getLanguageCode()->willReturn($this->locale);
        $this->structure->getWebspaceKey()->willReturn($this->webspaceKey);

        $this->property->getStructure()->willReturn($this->structure->reveal());
    }

    public function testGetType()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->assertEquals(ContentTypeInterface::PRE_SAVE, $type->getType());
    }

    public function testGetTemplate()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->assertEquals($this->template, $type->getTemplate());
    }

    public function testRead()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->property->getName()->willReturn('test');
        $this->node->hasProperty('test')->willReturn(true);
        $this->node->getPropertyValue('test', null)->willReturn(array(1, 2, 3));
        $this->property->setValue(array(1, 2, 3))->shouldBeCalled();

        $type->read(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testReadNull()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->property->getName()->willReturn('test');
        $this->node->hasProperty('test')->willReturn(true);
        $this->node->getPropertyValue('test', null)->willReturn(null);
        $this->property->setValue(array())->shouldBeCalled();

        $type->read(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testReadPropertyNotExists()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->property->getName()->willReturn('test');
        $this->node->hasProperty('test')->willReturn(false);
        $this->node->getPropertyValue(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->property->setValue(array())->shouldBeCalled();

        $type->read(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testReadForPreview()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->property->setValue(array(1, 2, 3))->shouldBeCalled();

        $type->readForPreview(
            array(1, 2, 3),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testReadForPreviewNull()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->property->setValue(array())->shouldBeCalled();

        $type->readForPreview(
            null,
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testWrite()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->property->getName()->willReturn('test');
        $this->property->getValue()->willReturn(array(1, 2, 3));
        $this->node->setProperty('test', array(1, 2, 3))->shouldBeCalled();

        $type->write(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->userId,
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testWriteNull()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->property->getName()->willReturn('test');
        $this->property->getValue()->willReturn(null);
        $this->property->setValue(Argument::any())->shouldNotBeCalled();
        $this->node->setProperty('test', array())->shouldBeCalled();

        $type->write(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->userId,
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testRemove()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $nodeProperty = $this->prophesize(\PHPCR\PropertyInterface::class);
        $nodeProperty->remove()->shouldBeCalled();
        $nodeProperty->setValue(Argument::any())->shouldNotBeCalled();
        $nodeProperty->getValue(Argument::any())->shouldNotBeCalled();

        $this->property->getName()->willReturn('test');
        $this->property->getValue()->shouldNotBeCalled();
        $this->node->hasProperty('test')->willReturn(true);
        $this->node->getProperty('test')->willReturn($nodeProperty->reveal());
        $this->node->setProperty(Argument::any(), Argument::any())->shouldNotBeCalled();

        $type->remove(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testGetContentData()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $contact1 = $this->prophesize(Contact::class);
        $contact2 = $this->prophesize(Contact::class);
        $contact3 = $this->prophesize(Contact::class);

        $this->property->getValue()->willReturn(array(1, 2, 3));
        $this->contactManager->getById(1, $this->locale)->willReturn($contact1->reveal());
        $this->contactManager->getById(2, $this->locale)->willReturn($contact2->reveal());
        $this->contactManager->getById(3, $this->locale)->willReturn($contact3->reveal());

        $contacts = $type->getContentData($this->property->reveal());

        $this->assertCount(3, $contacts);
        $this->assertEquals($contact1->reveal(), $contacts[0]);
        $this->assertEquals($contact2->reveal(), $contacts[1]);
        $this->assertEquals($contact3->reveal(), $contacts[2]);
    }

    public function testGetContentDataEmpty()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->property->getValue()->willReturn(array());
        $this->contactManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();

        $contacts = $type->getContentData($this->property->reveal());

        $this->assertCount(0, $contacts);
    }

    public function testGetContentDataNull()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->property->getValue()->willReturn(null);
        $this->contactManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();

        $contacts = $type->getContentData($this->property->reveal());

        $this->assertCount(0, $contacts);
    }

    public function testGetContentDataWrongType()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->property->getValue()->willReturn('blabla');
        $this->contactManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();

        $contacts = $type->getContentData($this->property->reveal());

        $this->assertCount(0, $contacts);
    }

    public function testGetViewData()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $view = $type->getViewData($this->property->reveal());

        $this->assertEquals(array(), $view);
    }

    public function testGetDefaultValue()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $defaultValue = $type->getDefaultValue();

        $this->assertEquals(array(), $defaultValue);
    }

    public function testGetDefaultParams()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $defaultParams = $type->getDefaultParams();

        $this->assertEquals(array(), $defaultParams);
    }

    public function testHasValue()
    {
        $type = new ContactSelectionContentType($this->contactManager->reveal(), $this->template);

        $this->property->getName()->willReturn('test');
        $this->node->hasProperty('test')->willReturn(true);

        $this->assertTrue(
            $type->hasValue(
                $this->node->reveal(),
                $this->property->reveal(),
                $this->webspaceKey,
                $this->locale,
                $this->segmentKey
            )
        );
    }
}
