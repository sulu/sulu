<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Infrastructure\Sulu\Content\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Api\Media as ApiMedia;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\MediaResourceLoader;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Security as WebspaceSecurity;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\SecurityBundle\Security;

class MediaResourceLoaderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<MediaManagerInterface>
     */
    private ObjectProphecy $mediaManager;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private ObjectProphecy $requestAnalyzer;

    private MediaResourceLoader $loader;

    public function setUp(): void
    {
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->requestAnalyzer->getWebspace()->willReturn(null);

        $this->loader = new MediaResourceLoader(
            $this->mediaManager->reveal(),
            null,
            null,
            $this->requestAnalyzer->reveal()
        );
    }

    public function testGetKey(): void
    {
        $this->assertSame('media', $this->loader::getKey());
    }

    public function testLoad(): void
    {
        $media1 = $this->createMedia(1);
        $media2 = $this->createMedia(3);

        $this->mediaManager->getByIds([1, 3], 'en', null)->willReturn([
            $media1,
            $media2,
        ])
            ->shouldBeCalled();

        $result = $this->loader->load([1, 3], 'en', []);

        $this->assertSame([
            1 => $media1,
            3 => $media2,
        ], $result);
    }

    public function testLoadWithSecureWebspaceAndAuthenticatedUser(): void
    {
        $webspace = new Webspace();
        $webspaceSecurity = new WebspaceSecurity();
        $webspaceSecurity->setSystem('website');
        $webspaceSecurity->setPermissionCheck(true);
        $webspace->setSecurity($webspaceSecurity);

        $user = $this->prophesize(UserInterface::class);

        $security = $this->prophesize(Security::class);
        $security->getUser()->willReturn($user->reveal());

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $loader = new MediaResourceLoader(
            $this->mediaManager->reveal(),
            $security->reveal(),
            [PermissionTypes::VIEW => 64],
            $requestAnalyzer->reveal()
        );

        $media1 = $this->createMedia(1);
        $media2 = $this->createMedia(3);

        $this->mediaManager->getByIds([1, 3], 'en', 64)->willReturn([
            $media1,
            $media2,
        ])
            ->shouldBeCalled();

        $result = $loader->load([1, 3], 'en', []);

        $this->assertSame([
            1 => $media1,
            3 => $media2,
        ], $result);
    }

    public function testLoadWithNonSecureWebspace(): void
    {
        $webspace = new Webspace();

        $user = $this->prophesize(UserInterface::class);

        $security = $this->prophesize(Security::class);
        $security->getUser()->willReturn($user->reveal());

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $loader = new MediaResourceLoader(
            $this->mediaManager->reveal(),
            $security->reveal(),
            [PermissionTypes::VIEW => 64],
            $requestAnalyzer->reveal()
        );

        $media1 = $this->createMedia(1);
        $media2 = $this->createMedia(3);

        // Should pass null as permission since webspace is not secure
        $this->mediaManager->getByIds([1, 3], 'en', null)->willReturn([
            $media1,
            $media2,
        ])
            ->shouldBeCalled();

        $result = $loader->load([1, 3], 'en', []);

        $this->assertSame([
            1 => $media1,
            3 => $media2,
        ], $result);
    }

    private static function createMedia(int $id): ApiMedia
    {
        $media = new Media();
        static::setPrivateProperty($media, 'id', $id);

        return new ApiMedia($media, 'en');
    }
}
