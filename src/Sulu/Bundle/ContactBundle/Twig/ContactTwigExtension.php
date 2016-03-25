<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Twig;

use Doctrine\Common\Cache\Cache;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;

/**
 * Extension to handle contacts in frontend.
 */
class ContactTwigExtension extends \Twig_Extension
{
    /**
     * @var ContactRepository
     */
    private $contactRepository;

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Cache $cache, ContactRepository $contactRepository)
    {
        $this->cache = $cache;
        $this->contactRepository = $contactRepository;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_resolve_contact', [$this, 'resolveContactFunction']),
        ];
    }

    /**
     * resolves user id to user data.
     *
     * @param int $id id to resolve
     *
     * @return Contact
     */
    public function resolveContactFunction($id)
    {
        if ($this->cache->contains($id)) {
            return $this->cache->fetch($id);
        }

        $contact = $this->contactRepository->find($id);
        if ($contact === null) {
            return;
        }

        $this->cache->save($id, $contact);

        return $contact;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'sulu_contact';
    }
}
