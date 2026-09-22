<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PreviewBundle\Admin\PreviewAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PreviewAdminTest extends TestCase
{
    use ProphecyTrait;

    private const TARGET_GROUPS_SECURITY_CONTEXT = 'sulu.settings.target-groups';

    /**
     * @var ObjectProphecy<UrlGeneratorInterface>
     */
    private $urlGenerator;

    /**
     * @var ObjectProphecy<SecurityCheckerInterface>
     */
    private $securityChecker;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->urlGenerator->generate(\Prophecy\Argument::cetera())->willReturn('/preview');

        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
    }

    public function testAudienceTargetingWithoutBundle(): void
    {
        $this->securityChecker->hasPermission(\Prophecy\Argument::cetera())->shouldNotBeCalled();

        $this->assertFalse($this->getConfig([])['audienceTargeting']);
    }

    public function testAudienceTargetingWithPermission(): void
    {
        $this->securityChecker->hasPermission(self::TARGET_GROUPS_SECURITY_CONTEXT, PermissionTypes::VIEW)
            ->willReturn(true)
            ->shouldBeCalled();

        $this->assertTrue($this->getConfigWithBundle()['audienceTargeting']);
    }

    public function testAudienceTargetingWithoutPermission(): void
    {
        $this->securityChecker->hasPermission(self::TARGET_GROUPS_SECURITY_CONTEXT, PermissionTypes::VIEW)
            ->willReturn(false)
            ->shouldBeCalled();

        $this->assertFalse($this->getConfigWithBundle()['audienceTargeting']);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function getConfigWithBundle(): array
    {
        return $this->getConfig(['SuluAudienceTargetingBundle' => 'Sulu\\Bundle\\AudienceTargetingBundle\\SuluAudienceTargetingBundle']);
    }

    /**
     * @param array<string, string> $bundles
     *
     * @return array<array-key, mixed>
     */
    private function getConfig(array $bundles): array
    {
        $previewAdmin = new PreviewAdmin(
            $this->urlGenerator->reveal(),
            200,
            'auto',
            $bundles,
            $this->securityChecker->reveal()
        );

        $config = $previewAdmin->getConfig();
        $this->assertIsArray($config);

        return $config;
    }
}
