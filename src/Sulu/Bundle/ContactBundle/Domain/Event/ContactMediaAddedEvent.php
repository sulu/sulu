<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

class ContactMediaAddedEvent extends DomainEvent
{
    /**
     * @var ContactInterface
     */
    private $contact;

    /**
     * @var MediaInterface
     */
    private $media;

    public function __construct(
        ContactInterface $contact,
        MediaInterface $media
    ) {
        parent::__construct();

        $this->contact = $contact;
        $this->media = $media;
    }

    public function getContact(): ContactInterface
    {
        return $this->contact;
    }

    public function getMedia(): MediaInterface
    {
        return $this->media;
    }

    public function getEventType(): string
    {
        return 'media_added';
    }

    public function getEventContext(): array
    {
        $fileVersionMeta = $this->getFileVersionMeta();

        return [
            'mediaId' => $this->media->getId(),
            'mediaTitle' => $fileVersionMeta ? $fileVersionMeta->getTitle() : null,
            'mediaTitleLocale' => $fileVersionMeta ? $fileVersionMeta->getLocale() : null,
        ];
    }

    public function getResourceKey(): string
    {
        return ContactInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->contact->getId();
    }

    public function getResourceTitle(): ?string
    {
        if ($this->contact instanceof Contact) {
            return $this->contact->getFullName();
        }

        return $this->contact->getFirstName() . ' ' . $this->contact->getLastName();
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::CONTACT_SECURITY_CONTEXT;
    }

    private function getFileVersionMeta(): ?FileVersionMeta
    {
        $file = $this->media->getFiles()[0] ?? null;
        $fileVersion = $file ? $file->getLatestFileVersion() : null;

        return $fileVersion ? $fileVersion->getDefaultMeta() : null;
    }
}
