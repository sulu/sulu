<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;

final readonly class NotFoundWelcomeListener
{
    public function __construct(
        private Environment $twig,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function renderWelcomePage(ExceptionEvent $event): void
    {
        if ($this->isFixturesLoaded()) {
            return;
        }

        $event->setResponse(new Response($this->twig->render(
            '@SuluWebsite/welcome.html.twig',
        )));
    }

    /**
     * Check if the fixtures are loaded by checking if there are collection types in the database.
     */
    public function isFixturesLoaded(): bool
    {
        return 0 !== $this->entityManager->getRepository(CollectionType::class)->count([]);
    }
}
