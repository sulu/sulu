<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\Datagrid;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\Datagrid\DatagridMetadataProvider;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DatagridMetadataProviderTest extends TestCase
{
    /**
     * @var DatagridMetadataProvider
     */
    private $datagridMetadataProvider;

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
        $this->datagridMetadataProvider = new DatagridMetadataProvider(
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

        $contactDatagridMetadata = $this->datagridMetadataProvider->getMetadata('contact', 'de');
        $contactDatagridFields = $contactDatagridMetadata->getFields();

        $this->assertEquals('firstName', $contactDatagridFields['firstName']->getName());
        $this->assertEquals('First name', $contactDatagridFields['firstName']->getLabel());
        $this->assertEquals('string', $contactDatagridFields['firstName']->getType());
        $this->assertEquals(true, $contactDatagridFields['firstName']->isSortable());
        $this->assertEquals(
            FieldDescriptorInterface::VISIBILITY_YES,
            $contactDatagridFields['firstName']->getVisibility()
        );
        $this->assertEquals('lastName', $contactDatagridFields['lastName']->getName());
        $this->assertEquals('Last name', $contactDatagridFields['lastName']->getLabel());
        $this->assertEquals('string', $contactDatagridFields['lastName']->getType());
        $this->assertEquals(false, $contactDatagridFields['lastName']->isSortable());
        $this->assertEquals(
            FieldDescriptorInterface::VISIBILITY_NO,
            $contactDatagridFields['lastName']->getVisibility()
        );

        $accountDatagridMetadata = $this->datagridMetadataProvider->getMetadata('account', 'en');
        $accountDatagridFields = $accountDatagridMetadata->getFields();

        $this->assertEquals('name', $accountDatagridFields['name']->getName());
        $this->assertEquals('Name', $accountDatagridFields['name']->getLabel());
        $this->assertEquals('string', $accountDatagridFields['name']->getType());
        $this->assertEquals(true, $accountDatagridFields['name']->isSortable());
        $this->assertEquals(
            FieldDescriptorInterface::VISIBILITY_YES,
            $accountDatagridFields['name']->getVisibility()
        );
    }

    public function testGetMetadataNotExisting()
    {
        $this->expectException(MetadataNotFoundException::class);

        $this->datagridMetadataProvider->getMetadata('not-existing', 'de');
    }
}
