<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\ListMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\XmlListMetadataLoader;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class XmlListMetadataLoaderTest extends TestCase
{
    /**
     * @var XmlListMetadataLoader
     */
    private $xmlListMetadataLoader;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    public function setUp()
    {
        $this->fieldDescriptorFactory = $this->prophesize(FieldDescriptorFactoryInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->xmlListMetadataLoader = new XmlListMetadataLoader(
            $this->fieldDescriptorFactory->reveal(),
            $this->translator->reveal()
        );
    }

    public function testGetMetadata()
    {
        $this->translator->trans('sulu_contact.firstname', [], 'admin', 'de')->willReturn('First name');
        $this->translator->trans('sulu_contact.lastname', [], 'admin', 'de')->willReturn('Last name');
        $this->translator->trans('sulu_contact.name', [], 'admin', 'en')->willReturn('Name');
        $this->fieldDescriptorFactory->getFieldDescriptors('contact')->willReturn(
            [
                new FieldDescriptor(
                    'firstName',
                    'sulu_contact.firstname',
                    FieldDescriptorInterface::VISIBILITY_YES,
                    FieldDescriptorInterface::SEARCHABILITY_NEVER,
                    'string',
                    true
                ),
                new FieldDescriptor(
                    'lastName',
                    'sulu_contact.lastname',
                    FieldDescriptorInterface::VISIBILITY_NO,
                    FieldDescriptorInterface::SEARCHABILITY_NEVER,
                    'string',
                    false
                ),
            ]
        );
        $this->fieldDescriptorFactory->getFieldDescriptors('account')->willReturn(
            [
                new FieldDescriptor(
                    'name',
                    'sulu_contact.name',
                    FieldDescriptorInterface::VISIBILITY_YES,
                    FieldDescriptorInterface::SEARCHABILITY_NEVER,
                    'string',
                    true
                ),
            ]
        );

        $contactListMetadata = $this->xmlListMetadataLoader->getMetadata('contact', 'de');
        $contactListFields = $contactListMetadata->getFields();

        $this->assertEquals('firstName', $contactListFields['firstName']->getName());
        $this->assertEquals('First name', $contactListFields['firstName']->getLabel());
        $this->assertEquals('string', $contactListFields['firstName']->getType());
        $this->assertEquals(true, $contactListFields['firstName']->isSortable());
        $this->assertEquals(
            FieldDescriptorInterface::VISIBILITY_YES,
            $contactListFields['firstName']->getVisibility()
        );
        $this->assertEquals('lastName', $contactListFields['lastName']->getName());
        $this->assertEquals('Last name', $contactListFields['lastName']->getLabel());
        $this->assertEquals('string', $contactListFields['lastName']->getType());
        $this->assertEquals(false, $contactListFields['lastName']->isSortable());
        $this->assertEquals(
            FieldDescriptorInterface::VISIBILITY_NO,
            $contactListFields['lastName']->getVisibility()
        );

        $accountListMetadata = $this->xmlListMetadataLoader->getMetadata('account', 'en');
        $accountListFields = $accountListMetadata->getFields();

        $this->assertEquals('name', $accountListFields['name']->getName());
        $this->assertEquals('Name', $accountListFields['name']->getLabel());
        $this->assertEquals('string', $accountListFields['name']->getType());
        $this->assertEquals(true, $accountListFields['name']->isSortable());
        $this->assertEquals(
            FieldDescriptorInterface::VISIBILITY_YES,
            $accountListFields['name']->getVisibility()
        );
    }

    public function testGetMetadataNotExisting()
    {
        $notExistingMetadata = $this->xmlListMetadataLoader->getMetadata('not-existing', 'de');
        $this->assertNull($notExistingMetadata);
    }
}
