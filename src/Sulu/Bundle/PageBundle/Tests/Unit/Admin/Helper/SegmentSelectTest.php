<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Admin\Helper;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PageBundle\Admin\Helper\SegmentSelect;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentSelectTest extends TestCase
{
    /**
     * @var WebspaceManager
     */
    private $webspaceManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SegmentSelect
     */
    private $segmentSelect;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var UserInterface
     */
    private $user;

    public function setUp(): void
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->token = $this->prophesize(TokenInterface::class);
        $this->user = $this->prophesize(UserInterface::class);

        $this->token->getUser()->willReturn($this->user->reveal());
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());

        $this->segmentSelect = new SegmentSelect(
            $this->webspaceManager->reveal(),
            $this->translator->reveal(),
            $this->tokenStorage->reveal()
        );
    }

    public function testGetValues()
    {
        $this->translator->trans('sulu_admin.none_selected', [], 'admin')->willReturn('None selected');
        $this->user->getLocale()->willReturn('en');

        $webspace1 = new Webspace();
        $segment1 = new Segment();
        $segment1->setKey('w');
        $segment1->setMetadata(['title' => ['en' => 'Winter']]);
        $segment2 = new Segment();
        $segment2->setKey('s');
        $segment2->setMetadata(['title' => ['en' => 'Summer']]);
        $webspace1->addSegment($segment1);
        $webspace1->addSegment($segment2);
        $this->webspaceManager->findWebspaceByKey('sulu_test')->willReturn($webspace1);

        $webspace2 = new Webspace();
        $this->webspaceManager->findWebspaceByKey('sulu_blog')->willReturn($webspace2);

        $this->assertEquals(
            $this->segmentSelect->getValues('sulu_test'),
            [
                ['title' => 'None selected'],
                ['name' => 'w', 'title' => 'Winter'],
                ['name' => 's', 'title' => 'Summer'],
            ]
        );

        $this->assertEquals(
            $this->segmentSelect->getValues('sulu_blog'),
            [
                ['title' => 'None selected'],
            ]
        );
    }
}
