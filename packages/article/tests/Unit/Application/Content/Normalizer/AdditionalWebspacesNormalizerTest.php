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

namespace Sulu\Article\Tests\Unit\Application\Content\Normalizer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Application\Content\Normalizer\AdditionalWebspacesNormalizer;
use Sulu\Article\Application\Webspace\WebspaceResolver;
use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Content\Application\ContentNormalizer\Normalizer\NormalizerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class AdditionalWebspacesNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<WebspaceResolver> */
    private ObjectProphecy $webspaceResolver;

    protected function setUp(): void
    {
        $this->webspaceResolver = $this->prophesize(WebspaceResolver::class);
    }

    protected function getAdditionalWebspacesNormalizerInstance(): NormalizerInterface
    {
        return new AdditionalWebspacesNormalizer($this->webspaceResolver->reveal());
    }

    public function testEnhanceNotImplementingInterface(): void
    {
        $normalizer = $this->getAdditionalWebspacesNormalizerInstance();

        $object = new \stdClass();
        $normalizedData = ['title' => 'Test'];

        $result = $normalizer->enhance($object, $normalizedData);

        $this->assertSame($normalizedData, $result);
    }

    public function testEnhanceCustomizeWebspaceSettingsTrue(): void
    {
        $normalizer = $this->getAdditionalWebspacesNormalizerInstance();

        $object = $this->prophesize(AdditionalWebspacesInterface::class);
        $object->getCustomizeWebspaceSettings()->willReturn(true)->shouldBeCalled();

        $normalizedData = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $result = $normalizer->enhance($object->reveal(), $normalizedData);

        $this->assertSame($normalizedData, $result);
    }

    public function testEnhanceCustomizeWebspaceSettingsFalse(): void
    {
        $normalizer = $this->getAdditionalWebspacesNormalizerInstance();

        $locale = 'en';
        $resolvedMainWebspace = 'resolved-main';
        $resolvedAdditionalWebspaces = ['resolved-additional'];

        $object = $this->prophesize(DimensionContentInterface::class);
        $object->willImplement(AdditionalWebspacesInterface::class);
        $object->getCustomizeWebspaceSettings()->willReturn(false)->shouldBeCalled();
        $object->getLocale()->willReturn($locale)->shouldBeCalled();

        $this->webspaceResolver->resolveMainWebspace($object->reveal(), $locale)->willReturn($resolvedMainWebspace)->shouldBeCalled();
        $this->webspaceResolver->resolveAdditionalWebspaces($object->reveal(), $locale)->willReturn($resolvedAdditionalWebspaces)->shouldBeCalled();

        $normalizedData = [
            'customizeWebspaceSettings' => false,
            'mainWebspace' => null,
            'additionalWebspaces' => null,
        ];

        $result = $normalizer->enhance($object->reveal(), $normalizedData);

        $expected = [
            'customizeWebspaceSettings' => false,
            'mainWebspace' => $resolvedMainWebspace,
            'additionalWebspaces' => $resolvedAdditionalWebspaces,
        ];

        $this->assertSame($expected, $result);
    }

    public function testEnhanceCustomizeWebspaceSettingsFalseWithNullLocale(): void
    {
        $normalizer = $this->getAdditionalWebspacesNormalizerInstance();

        $object = $this->prophesize(DimensionContentInterface::class);
        $object->willImplement(AdditionalWebspacesInterface::class);
        $object->getCustomizeWebspaceSettings()->willReturn(false)->shouldBeCalled();
        $object->getLocale()->willReturn(null)->shouldBeCalled();

        // When locale is null, the resolver methods should not be called

        $normalizedData = [
            'customizeWebspaceSettings' => false,
            'mainWebspace' => 'custom-main',
            'additionalWebspaces' => ['custom-additional'],
        ];

        $result = $normalizer->enhance($object->reveal(), $normalizedData);

        $expected = [
            'customizeWebspaceSettings' => false,
            'mainWebspace' => null,
            'additionalWebspaces' => [],
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetIgnoredAttributesNotImplementingInterface(): void
    {
        $normalizer = $this->getAdditionalWebspacesNormalizerInstance();

        $object = new \stdClass();

        $result = $normalizer->getIgnoredAttributes($object);

        $this->assertSame([], $result);
    }

    public function testGetIgnoredAttributesImplementingInterface(): void
    {
        $normalizer = $this->getAdditionalWebspacesNormalizerInstance();

        $object = $this->prophesize(AdditionalWebspacesInterface::class);

        $result = $normalizer->getIgnoredAttributes($object->reveal());

        $this->assertSame([], $result);
    }
}
