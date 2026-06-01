<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Command;

use Doctrine\ORM\NoResultException;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal this class should not be instated by a project
 *           Use instead a command event listener to
 *           extend or change the commands behaviours
 */
#[AsCommand(name: 'sulu:security:user-unlock', description: 'Unlock a user.')]
final class UnlockUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserManager $userManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('identifier', InputArgument::REQUIRED, 'The username or email of the user to unlock');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('identifier')) {
            return;
        }

        $question = new Question('Please enter the username or email: ');
        $question->setValidator(function($identifier) {
            if (empty($identifier)) {
                throw new \InvalidArgumentException('Identifier can not be empty');
            }

            return $identifier;
        });

        $input->setArgument('identifier', $this->getHelper('question')->ask($input, $output, $question));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $identifier = $input->getArgument('identifier');

        try {
            $user = $this->userRepository->findUserByIdentifier($identifier);
        } catch (NoResultException) {
            $io->error(\sprintf('User "%s" not found.', $identifier));

            return Command::FAILURE;
        }

        if (!$user->getLocked()) {
            $io->info(\sprintf('User "%s" is already unlocked, doing nothing.', $identifier));

            return Command::SUCCESS;
        }

        $this->userManager->unlockUser($user->getId());

        $io->success(\sprintf('User "%s" has been unlocked.', $identifier));

        return Command::SUCCESS;
    }
}
