<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Application\Manager;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PreviewBundle\Application\Manager\PreviewLinkManager;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;

class PreviewLinkManagerTest extends TestCase
{
    /**
     * @var PreviewLinkRepositoryInterface|ObjectProphecy
     */
    private $previewLinkRepository;

    /**
     * @var PreviewLinkManager
     */
    private $previewLinkManager;

    protected function setUp(): void
    {
        $this->previewLinkRepository = $this->prophesize(PreviewLinkRepositoryInterface::class);

        $this->previewLinkManager = new PreviewLinkManager(
            $this->previewLinkRepository->reveal()
        );
    }

    public function testGenerate(): void
    {
        $resourceKey = 'pages';
        $resourceId = 'aac8f317-d479-457c-a6e4-d95a3f19c0a6';
        $locale = 'en';

        $previewLink = $this->prophesize(PreviewLinkInterface::class);
        $this->previewLinkRepository->createNew($resourceKey, $resourceId, $locale, [])
            ->shouldBeCalled()
            ->willReturn($previewLink->reveal());

        $this->previewLinkRepository->add($previewLink->reveal())->shouldBeCalled();
        $this->previewLinkRepository->commit()->shouldBeCalled();

        $result = $this->previewLinkManager->generate($resourceKey, $resourceId, $locale, []);
        static::assertEquals($previewLink->reveal(), $result);
    }

    public function testRevoke(): void
    {
        $resourceKey = 'pages';
        $resourceId = 'aac8f317-d479-457c-a6e4-d95a3f19c0a6';
        $locale = 'en';

        $previewLink = $this->prophesize(PreviewLinkInterface::class);
        $this->previewLinkRepository->findByResource($resourceKey, $resourceId, $locale)
            ->shouldBeCalled()
            ->willReturn($previewLink->reveal());

        $this->previewLinkRepository->remove($previewLink->reveal())->shouldBeCalled();
        $this->previewLinkRepository->commit()->shouldBeCalled();

        $this->previewLinkManager->revoke($resourceKey, $resourceId, $locale);
    }

    public function testRevokeNotFound(): void
    {
        $resourceKey = 'pages';
        $resourceId = 'aac8f317-d479-457c-a6e4-d95a3f19c0a6';
        $locale = 'en';

        $this->previewLinkRepository->findByResource($resourceKey, $resourceId, $locale)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->previewLinkRepository->remove(Argument::any())->shouldNotBeCalled();
        $this->previewLinkRepository->commit()->shouldNotBeCalled();

        $this->previewLinkManager->revoke($resourceKey, $resourceId, $locale);
    }
}
