<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Metadata;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Bundle\SecurityBundle\Metadata\PasswordPolicyFormMetadataVisitor;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordPolicyFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    public function createInstance(
        ?string $passwordPattern = null,
        ?string $passwordInformationTranslationKey = null
    ): PasswordPolicyFormMetadataVisitor {
        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans(Argument::any(), [], 'admin')->will(function($arguments) {
            return $arguments[0];
        });
        $translator->getLocale()->willReturn('en');

        return new PasswordPolicyFormMetadataVisitor(
            $translator->reveal(),
            $passwordPattern,
            $passwordInformationTranslationKey
        );
    }

    public function testVisitNoPattern(): void
    {
        $passwordMetadata = new FieldMetadata('password');
        $formSchema = new SchemaMetadata();

        $formMetadata = new FormMetadata();
        $formMetadata->addItem($passwordMetadata);
        $formMetadata->setSchema($formSchema);

        $instance = $this->createInstance();
        $instance->visitFormMetadata($formMetadata, 'en');
        $this->assertNull($passwordMetadata->getDescription('en'));
        $this->assertSame([
            'type' => [
                'number',
                'string',
                'boolean',
                'object',
                'array',
                'null',
            ],
        ], $formMetadata->getSchema()->toJsonSchema());
    }

    public function testVisitWrongFormKey(): void
    {
        $passwordMetadata = new FieldMetadata('password');
        $formSchema = new SchemaMetadata();

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('test_details');
        $formMetadata->addItem($passwordMetadata);
        $formMetadata->setSchema($formSchema);

        $instance = $this->createInstance('.{8,}', 'test_key');
        $instance->visitFormMetadata($formMetadata, 'en');
        $this->assertNull($passwordMetadata->getDescription('en'));
        $this->assertSame([
            'type' => [
                'number',
                'string',
                'boolean',
                'object',
                'array',
                'null',
            ],
        ], $formMetadata->getSchema()->toJsonSchema());
    }

    public function testVisitUserDetails(): void
    {
        $passwordMetadata = new FieldMetadata('password');
        $formSchema = new SchemaMetadata();

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('user_details');
        $formMetadata->addItem($passwordMetadata);
        $formMetadata->setSchema($formSchema);

        $instance = $this->createInstance('.{8,}', 'test_key');
        $instance->visitFormMetadata($formMetadata, 'en');
        $this->assertSame('test_key', $passwordMetadata->getDescription('en'));
        $this->assertSame([
            'allOf' => [
                [
                    'type' => [
                        'number',
                        'string',
                        'boolean',
                        'object',
                        'array',
                        'null',
                    ],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'password' => [
                            'type' => 'string',
                            'pattern' => '.{8,}',
                        ],
                    ],
                ],
            ],
        ], $formMetadata->getSchema()->toJsonSchema());
    }

    public function testVisitProfileDetails(): void
    {
        $passwordMetadata = new FieldMetadata('password');
        $formSchema = new SchemaMetadata();

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('profile_details');
        $formMetadata->addItem($passwordMetadata);
        $formMetadata->setSchema($formSchema);

        $instance = $this->createInstance('.{8,}', 'test_key');
        $instance->visitFormMetadata($formMetadata, 'en');
        $this->assertSame('test_key', $passwordMetadata->getDescription('en'));
        $this->assertSame([
            'allOf' => [
                [
                    'type' => [
                        'number',
                        'string',
                        'boolean',
                        'object',
                        'array',
                        'null',
                    ],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'password' => [
                            'type' => 'string',
                            'pattern' => '.{8,}',
                        ],
                    ],
                ],
            ],
        ], $formMetadata->getSchema()->toJsonSchema());
    }
}
