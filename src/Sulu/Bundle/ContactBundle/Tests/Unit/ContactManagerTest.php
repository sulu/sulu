<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Entity\AccountRepositoryInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\ContactBundle\Entity\ContactTitleRepository;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;

class ContactManagerTest extends TestCase
{
    use ProphecyTrait;

    private \Sulu\Bundle\ContactBundle\Contact\ContactManager $contactManager;

    /**
     * @var ObjectProphecy<ObjectManager>
     */
    private ObjectProphecy $em;

    /**
     * @var ObjectProphecy<TagManagerInterface>
     */
    private ObjectProphecy $tagManager;

    /**
     * @var ObjectProphecy<MediaManagerInterface>
     */
    private ObjectProphecy $mediaManager;

    /**
     * @var ObjectProphecy<AccountRepositoryInterface>
     */
    private ObjectProphecy $accountRepository;

    /**
     * @var ObjectProphecy<ContactTitleRepository>
     */
    private ObjectProphecy $contactTitleRepository;

    /**
     * @var ObjectProphecy<ContactRepository>
     */
    private ObjectProphecy $contactRepository;

    /**
     * @var ObjectProphecy<MediaRepositoryInterface>
     */
    private ObjectProphecy $mediaRepository;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private ObjectProphecy $eventCollector;

    /**
     * @var ObjectProphecy<TrashManagerInterface>
     */
    private ObjectProphecy $trashManager;

    /**
     * @var ObjectProphecy<UserRepository>
     */
    private ObjectProphecy $userRepository;

    protected function setUp(): void
    {
        $this->em = $this->prophesize(ObjectManager::class);
        $this->tagManager = $this->prophesize(TagManagerInterface::class);
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->accountRepository = $this->prophesize(AccountRepositoryInterface::class);
        $this->contactTitleRepository = $this->prophesize(ContactTitleRepository::class);
        $this->contactRepository = $this->prophesize(ContactRepository::class);
        $this->mediaRepository = $this->prophesize(MediaRepositoryInterface::class);
        $this->eventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->userRepository = $this->prophesize(UserRepository::class);
        $this->trashManager = $this->prophesize(TrashManagerInterface::class);

        $this->contactManager = new ContactManager(
            $this->em->reveal(),
            $this->tagManager->reveal(),
            $this->mediaManager->reveal(),
            $this->accountRepository->reveal(),
            $this->contactTitleRepository->reveal(),
            $this->contactRepository->reveal(),
            $this->mediaRepository->reveal(),
            $this->eventCollector->reveal(),
            $this->userRepository->reveal(),
            $this->trashManager->reveal()
        );
    }

    public function testAddTag(): void
    {
        $contact = $this->prophesize(Contact::class);
        $tag = $this->prophesize(TagInterface::class);

        $contact->getContactAddresses()->willReturn(new ArrayCollection());
        $contact->getTags()->willReturn(new ArrayCollection());
        $this->tagManager->findOrCreateByName('testtag')->willReturn($tag->reveal());
        $contact->addTag($tag->reveal())->shouldBeCalled();

        $this->contactManager->addNewContactRelations($contact->reveal(), ['tags' => ['testtag']]);
    }
}
