<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\SecurityBundle\Admin\SecurityAdmin;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityAdminTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<UrlGeneratorInterface>
     */
    private $urlGenerator;

    /**
     * @var ObjectProphecy<AdminPool>
     */
    private $adminPool;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->adminPool->getSecurityContextsWithPlaceholder()->willReturn([]);

        $this->urlGenerator->generate('sulu_security.cget_security-contexts')->willReturn('/admin/api/security-contexts');
    }

    public function testGetConfigWithoutTwoFactorMethods(): void
    {
        $this->urlGenerator->generate(Argument::containingString('two-factor'))->shouldNotBeCalled();

        $config = $this->createSecurityAdmin([])->getConfig();

        $this->assertNotNull($config);
        $this->assertSame(['contexts' => '/admin/api/security-contexts'], $config['endpoints']);
        $this->assertSame([], $config['twoFactorMethods']);
    }

    public function testGetConfigWithTwoFactorMethods(): void
    {
        $this->urlGenerator->generate('sulu_security.post_profile_two-factor_setup')->willReturn('/setup');
        $this->urlGenerator->generate('sulu_security.post_profile_two-factor_confirm')->willReturn('/confirm');
        $this->urlGenerator->generate('sulu_security.post_profile_two-factor_backup-codes')->willReturn('/backup-codes');
        $this->urlGenerator->generate('sulu_security.delete_profile_two-factor')->willReturn('/delete');

        $config = $this->createSecurityAdmin(['email', 'totp'])->getConfig();

        $this->assertNotNull($config);
        $this->assertSame(
            [
                'contexts' => '/admin/api/security-contexts',
                'twoFactorSetup' => '/setup',
                'twoFactorConfirm' => '/confirm',
                'twoFactorBackupCodes' => '/backup-codes',
                'twoFactorDelete' => '/delete',
            ],
            $config['endpoints']
        );
        $this->assertSame(['email', 'totp'], $config['twoFactorMethods']);
    }

    /**
     * @param string[] $twoFactorMethods
     */
    private function createSecurityAdmin(array $twoFactorMethods): SecurityAdmin
    {
        return new SecurityAdmin(
            $this->prophesize(ViewBuilderFactoryInterface::class)->reveal(),
            $this->prophesize(SecurityCheckerInterface::class)->reveal(),
            $this->urlGenerator->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
            $this->adminPool->reveal(),
            [],
            false,
            null,
            null,
            $twoFactorMethods,
        );
    }
}
