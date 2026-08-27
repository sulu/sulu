<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\View;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGenerator;
use Sulu\Bundle\AdminBundle\Admin\View\ViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Exception\ResourceViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResourceViewUrlGeneratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ViewUrlGeneratorInterface>
     */
    private ObjectProphecy $viewUrlGenerator;

    public function setUp(): void
    {
        $this->viewUrlGenerator = $this->prophesize(ViewUrlGeneratorInterface::class);
    }

    /**
     * @param array<string, array{views?: array<string, string>}> $resources
     */
    private function createResourceViewUrlGenerator(array $resources): ResourceViewUrlGenerator
    {
        return new ResourceViewUrlGenerator($this->viewUrlGenerator->reveal(), $resources);
    }

    public function testGenerate(): void
    {
        $resources = [
            'contacts' => [
                'views' => [
                    'detail' => 'sulu_contact.contact_edit_form.details',
                ],
            ],
        ];

        $this->viewUrlGenerator->generate(
            'sulu_contact.contact_edit_form.details',
            ['id' => 1],
            UrlGeneratorInterface::ABSOLUTE_PATH
        )->willReturn('/admin/#/contacts/1/details');

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator($resources);

        $this->assertSame(
            '/admin/#/contacts/1/details',
            $resourceViewUrlGenerator->generate('contacts', 'detail', ['id' => 1])
        );
    }

    public function testGenerateWithReferenceType(): void
    {
        $resources = [
            'contacts' => [
                'views' => [
                    'detail' => 'sulu_contact.contact_edit_form.details',
                ],
            ],
        ];

        $this->viewUrlGenerator->generate(
            'sulu_contact.contact_edit_form.details',
            ['id' => 1],
            UrlGeneratorInterface::ABSOLUTE_URL
        )->willReturn('https://example.org/admin/#/contacts/1/details');

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator($resources);

        $this->assertSame(
            'https://example.org/admin/#/contacts/1/details',
            $resourceViewUrlGenerator->generate('contacts', 'detail', ['id' => 1], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    public function testGenerateThrowsExceptionForUnknownResourceKey(): void
    {
        $this->expectException(ResourceViewNotFoundException::class);

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator([]);
        $resourceViewUrlGenerator->generate('not_existing', 'detail');
    }

    public function testGenerateThrowsExceptionForUnconfiguredResourceView(): void
    {
        $this->expectException(ResourceViewNotFoundException::class);

        $resources = [
            'contacts' => [
                'views' => [
                    'list' => 'sulu_contact.contacts',
                ],
            ],
        ];

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator($resources);
        $resourceViewUrlGenerator->generate('contacts', 'detail');
    }

    public function testGeneratePropagatesViewNotFoundException(): void
    {
        $this->expectException(ViewNotFoundException::class);

        $resources = [
            'contacts' => [
                'views' => [
                    'detail' => 'sulu_contact.not_existing',
                ],
            ],
        ];

        $this->viewUrlGenerator->generate('sulu_contact.not_existing', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willThrow(new ViewNotFoundException('sulu_contact.not_existing'));

        $resourceViewUrlGenerator = $this->createResourceViewUrlGenerator($resources);
        $resourceViewUrlGenerator->generate('contacts', 'detail');
    }
}
