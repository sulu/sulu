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

use Doctrine\DBAL\ConnectionException as DBALConnectionException;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;

/**
 * @internal this is an internal class which should not be used by a project
 */
final readonly class NotFoundWelcomeListener
{
    public const STATE_DATABASE_MISSING = 'DATABASE_MISSING';
    public const STATE_FIXTURES_MISSING = 'DATABASE_FIXTURES_MISSING';
    public const STATE_DATABASE_LOADED = 'DATABASE_FIXTURES_LOADED';

    public function __construct(
        private string $projectDirectory,
        private Environment $twig,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function renderWelcomePage(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        $state = match (true) {
            $throwable instanceof DBALConnectionException => self::STATE_DATABASE_MISSING,
            $throwable instanceof ConnectionException => self::STATE_DATABASE_MISSING,
            $throwable instanceof TableNotFoundException && \str_contains($throwable->getMessage(), 'ro_routes') => self::STATE_FIXTURES_MISSING,
            default => $this->getFixturesState(),
        };

        if (self::STATE_DATABASE_LOADED === $state) {
            return;
        }

        $template = $this->twig->render(
            '@SuluWebsite/welcome.html.twig',
            [
                'steps' => $this->getSteps($state),
                'workingDirectory' => $this->projectDirectory,
            ]
        );

        $event->setResponse(new Response($template));
    }

    public function getFixturesState(): string
    {
        try {
            $fixturesCount = $this->entityManager->getRepository(CollectionType::class)->count([]);

            return match ($fixturesCount) {
                0 => self::STATE_FIXTURES_MISSING,
                default => self::STATE_DATABASE_LOADED,
            };
        } catch (\Exception $e) {
            return self::STATE_DATABASE_MISSING;
        }
    }

    /**
     * @return iterable<int,array<string,mixed>>
     */
    private function getSteps(string $state): iterable
    {
        yield [
            'headline' => 'Sulu installed',
            'status' => 'Done',
            'badge' => 'checked',
        ];

        $isActive = true;
        if (self::STATE_DATABASE_MISSING === $state) {
            yield [
                'headline' => 'Database configuration invalid',
                'status' => "Check the .env file for the correct connection string\nMake sure the docker container is running with `docker compose up`",
                'badge' => 'active',
            ];
            $isActive = false;
        } else {
            yield [
                'headline' => 'Database connected',
                'status' => 'Done',
                'badge' => 'checked',
            ];
        }

        yield [
            'headline' => 'Load the inital data',
            'status' => 'Copy the command on the right',
            'badge' => $isActive ? 'active' : 'empty',
        ];

        yield [
            'headline' => 'Open the admin',
            'status' => 'After the next reload',
            'badge' => 'empty',
        ];
    }
}
