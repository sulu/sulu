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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'sulu:security:user:lock', description: 'Lock or unlock a user.')]
class LockUserCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private UserManager $userManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user to lock')
            ->addOption('unlock', null, InputOption::VALUE_NONE, 'Unlock the user instead of locking it');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('username')) {
            return;
        }

        $question = new Question('Please enter the username: ');
        $question->setValidator(function($username) {
            if (empty($username)) {
                throw new \InvalidArgumentException('Username can not be empty');
            }

            return $username;
        });

        $input->setArgument('username', $this->getHelper('question')->ask($input, $output, $question));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $unlock = $input->getOption('unlock');
        $action = $unlock ? 'unlock' : 'lock';

        try {
            $user = $this->userRepository->findUserByUsername($username);
        } catch (NoResultException) {
            $io->error(\sprintf('User "%s" not found.', $username));

            return Command::FAILURE;
        }

        if ($user->getLocked() === !$unlock) {
            $io->warning(\sprintf('User "%s" is already %sed.', $username, $action));

            return Command::SUCCESS;
        }

        if (!$io->confirm(\sprintf('Are you sure you want to %s the user "%s"?', $action, $username))) {
            return Command::SUCCESS;
        }

        if ($unlock) {
            $this->userManager->unlockUser($user->getId());
        } else {
            $this->userManager->lockUser($user->getId());
        }

        $io->success(\sprintf('User "%s" has been %sed.', $username, $action));

        return Command::SUCCESS;
    }
}
