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

use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\Exception\RoleNotFoundException;
use Sulu\Bundle\SecurityBundle\Factory\UserFactoryInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'sulu:security:user:create';

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var UserFactoryInterface
     */
    private $userFactory;

    /**
     * @var string[]
     */
    private $locales;

    public function __construct(
        UserRepository $userRepository,
        RoleRepositoryInterface $roleRepository,
        UserFactoryInterface $userFactory,
        array $locales
    ) {
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->locales = $locales;
        $this->userFactory = $userFactory;
    }

    protected function configure(): void
    {
        $this->setDescription('Create a user.')
            ->setDefinition(
                [
                    new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                    new InputArgument('firstName', InputArgument::REQUIRED, 'The FirstName'),
                    new InputArgument('lastName', InputArgument::REQUIRED, 'The LastName'),
                    new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                    new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
                    new InputArgument('role', InputArgument::REQUIRED, 'The role'),
                    new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                ]
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $email = $input->getArgument('email');
        $locale = $input->getArgument('locale');
        $roleName = $input->getArgument('role');
        $password = $input->getArgument('password');

        $existing = $this->userRepository->findOneBy(['username' => $username]);

        if ($existing) {
            $output->writeln(\sprintf('<error>User "%s" already exists</error>',
                $username
            ));

            return 1;
        }

        if (!\in_array($locale, $this->locales)) {
            $output->writeln(\sprintf(
                'Given locale "%s" is invalid, must be one of "%s"',
                $locale, \implode('", "', $this->locales)
            ));

            return 1;
        }

        try {
            $this->userFactory->create($username, $firstName, $lastName, $email, $locale, $password, $roleName);
        } catch (RoleNotFoundException $roleNotFoundException) {
            $output->writeln(\sprintf('<error>Role "%s" not found. The following roles are available: "%s"</error>',
                $roleName,
                \implode('", "', $this->getRoleNames())
            ));

            return 1;
        }

        $output->writeln(
            \sprintf('Created user "<comment>%s</comment>" in role "<comment>%s</comment>"', $username, $roleName)
        );

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $roleNames = $this->getRoleNames();
        $helper = $this->getHelper('question');

        $userRepository = $this->userRepository;

        if (!$input->getArgument('username')) {
            $question = new Question('Please choose a username: ');
            $question->setValidator(
                function($username) use ($userRepository) {
                    if (empty($username)) {
                        throw new \InvalidArgumentException('Username can not be empty');
                    }

                    $users = $userRepository->findBy(['username' => $username]);
                    if (\count($users) > 0) {
                        throw new \InvalidArgumentException(\sprintf('Username "%s" is not unique', $username));
                    }

                    return $username;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('username', $value);
        }

        if (!$input->getArgument('firstName')) {
            $question = new Question('Please choose a FirstName: ');
            $question->setValidator(
                function($firstName) {
                    if (empty($firstName)) {
                        throw new \InvalidArgumentException('FirstName can not be empty');
                    }

                    return $firstName;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('firstName', $value);
        }

        if (!$input->getArgument('lastName')) {
            $question = new Question('Please choose a LastName: ');
            $question->setValidator(
                function($lastName) {
                    if (empty($lastName)) {
                        throw new \InvalidArgumentException('LastName can not be empty');
                    }

                    return $lastName;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('lastName', $value);
        }

        if (!$input->getArgument('email')) {
            $question = new Question('Please choose a Email: ');
            $question->setValidator(
                function($email) use ($userRepository) {
                    if (empty($email)) {
                        $email = null;
                    }
                    if (null !== $email) {
                        $users = $userRepository->findBy(['email' => $email]);
                        if (\count($users) > 0) {
                            throw new \InvalidArgumentException(\sprintf('Email "%s" is not unique', $email));
                        }
                    }

                    return $email;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('email', $value);
        }

        if (!$input->getArgument('locale')) {
            $question = new ChoiceQuestion('Please choose a locale', $this->locales);
            $value = $helper->ask($input, $output, $question);
            $input->setArgument('locale', $value);
        }

        if (!$input->getArgument('role')) {
            $question = new ChoiceQuestion(
                'Please choose a role: ',
                $roleNames,
                0
            );
            $value = $helper->ask($input, $output, $question);
            $input->setArgument('role', $value);
        }

        if (!$input->getArgument('password')) {
            $question = new Question('Please choose a Password: ');
            $question->setHidden(true);
            $question->setValidator(
                function($password) {
                    if (empty($password)) {
                        throw new \InvalidArgumentException('Password can not be empty');
                    }

                    return $password;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('password', $value);
        }
    }

    /**
     * Return the names of all the roles.
     *
     * @throws \RuntimeException If no roles exist
     */
    private function getRoleNames(): array
    {
        $roleNames = $this->roleRepository->getRoleNames();

        if (empty($roleNames)) {
            throw new \RuntimeException(\sprintf(
                'The system currently has no roles. Use the "sulu:security:role:create" command to create roles.'
            ));
        }

        return $roleNames;
    }
}
