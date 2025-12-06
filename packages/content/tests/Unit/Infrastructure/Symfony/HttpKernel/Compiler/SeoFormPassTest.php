<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Infrastructure\Symfony\HttpKernel\Compiler;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Domain\Model\SeoInterface;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\SeoFormPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SeoFormPassTest extends TestCase
{
    use ProphecyTrait;

    public function testProcess(): void
    {
        $directories = [
            __DIR__ . \DIRECTORY_SEPARATOR . 'SeoForms',
        ];

        $container = $this->prophesize(ContainerBuilder::class);
        $container->getParameter('sulu_admin.forms.directories')
            ->shouldBeCalled()
            ->willReturn($directories);

        $container->setParameter('sulu_content.content_seo_forms', [
            'content_seo_metadata' => [
                'instanceOf' => SeoInterface::class,
                'priority' => 100,
            ],
        ])->shouldBeCalled();

        $seoFormPass = new SeoFormPass();
        $seoFormPass->process($container->reveal());
    }

    public function testProcessNoInstanceOf(): void
    {
        $directories = [
            __DIR__ . \DIRECTORY_SEPARATOR . 'InvalidSeoForms' . \DIRECTORY_SEPARATOR . 'NoInterface',
        ];

        $container = $this->prophesize(ContainerBuilder::class);
        $container->getParameter('sulu_admin.forms.directories')
            ->shouldBeCalled()
            ->willReturn($directories);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tags with the name "sulu_content.content_seo_form" must have a valid "instanceOf" attribute!');

        $seoFormPass = new SeoFormPass();
        $seoFormPass->process($container->reveal());
    }

    public function testProcessNoKey(): void
    {
        $directories = [
            __DIR__ . \DIRECTORY_SEPARATOR . 'InvalidSeoForms' . \DIRECTORY_SEPARATOR . 'NoKey',
        ];

        $container = $this->prophesize(ContainerBuilder::class);
        $container->getParameter('sulu_admin.forms.directories')
            ->shouldBeCalled()
            ->willReturn($directories);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Forms must have a valid "key" element!');

        $seoFormPass = new SeoFormPass();
        $seoFormPass->process($container->reveal());
    }

    public function testProcessInvalidTag(): void
    {
        $directories = [
            __DIR__ . \DIRECTORY_SEPARATOR . 'InvalidSeoForms' . \DIRECTORY_SEPARATOR . 'InvalidTag',
        ];

        $container = $this->prophesize(ContainerBuilder::class);
        $container->getParameter('sulu_admin.forms.directories')
            ->shouldBeCalled()
            ->willReturn($directories);

        $container->setParameter('sulu_content.content_seo_forms', [])
            ->shouldBeCalled();

        $seoFormPass = new SeoFormPass();
        $seoFormPass->process($container->reveal());
    }
}
