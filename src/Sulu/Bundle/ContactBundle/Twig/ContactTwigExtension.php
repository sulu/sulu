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

use Psr\Cache\InvalidArgumentException;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension to handle contacts in frontend.
 */
class ContactTwigExtension extends AbstractExtension
{
    public function __construct(
        private ArrayAdapter $cache,
        private ContactRepository $contactRepository,
    ) {
    }

    /**
     * @return array<TwigFunction>
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_resolve_contact', $this->resolveContactFunction(...)),
        ];
    }

    /**
     * @param mixed $id id to resolve
     *
     * @return ContactInterface|null
     *
     * @throws InvalidArgumentException
     */
    public function resolveContactFunction($id)
    {
        if (!\is_string($id) && !\is_int($id)) {
            return null;
        }

        return $this->cache->get((string) $id, function() use ($id) {
            return $this->contactRepository->find($id);
        });
    }
}
