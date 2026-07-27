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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'sulu:security:user:reset-two-factor', description: 'Reset the two factor authentication of a user.')]
class ResetTwoFactorCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDefinition([
            new InputArgument('username', InputArgument::REQUIRED, 'The username of the user'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);

        /** @var string $username */
        $username = $input->getArgument('username');

        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            $ui->error(\sprintf('User "%s" not found.', $username));

            return Command::FAILURE;
        }

        $twoFactor = $user->getTwoFactor();

        if (!$twoFactor) {
            $ui->warning(\sprintf('User "%s" has no two factor authentication configured.', $username));

            return Command::SUCCESS;
        }

        $user->setTwoFactor(null);
        $this->entityManager->remove($twoFactor);
        $this->entityManager->flush();

        $ui->success(\sprintf('Two factor authentication of user "%s" was reset.', $username));

        return Command::SUCCESS;
    }
}
