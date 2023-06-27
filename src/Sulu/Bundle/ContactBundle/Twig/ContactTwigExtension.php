<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Twig;

use Doctrine\Common\Cache\Cache;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension to handle contacts in frontend.
 */
class ContactTwigExtension extends AbstractExtension
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
     * @return array<TwigFunction>
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_resolve_contact', [$this, 'resolveContactFunction']),
        ];
    }

    /**
     * @param int $id id to resolve
     *
     * @return ContactInterface|null
     */
    public function resolveContactFunction($id)
    {
        if ($this->cache->contains($id)) {
            return $this->cache->fetch($id);
        }

        $contact = $this->contactRepository->find($id);
        if (null === $contact) {
            return null;
        }

        $this->cache->save($id, $contact);

        return $contact;
    }
}
