<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Command;

use DateTime;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class CreateUserCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this->setName('sulu:security:user:create')
            ->setDescription('Create a user.')
            ->setDefinition(
                array(
                    new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                    new InputArgument('firstName', InputArgument::REQUIRED, 'The FirstName'),
                    new InputArgument('lastName', InputArgument::REQUIRED, 'The LastName'),
                    new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                    new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
                    new InputArgument('role', InputArgument::REQUIRED, 'The role'),
                    new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                )
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localizations = $this->getContainer()->get('sulu.core.localization_manager')->getLocalizations();
        $locales = array();
        $userLocales = $this->getContainer()->getParameter('sulu_core.locales');

        foreach ($localizations as $localization) {
            $locales[] = $localization->getLocalization();
        }

        $username = $input->getArgument('username');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $email = $input->getArgument('email');
        $locale = $input->getArgument('locale');
        $roleName = $input->getArgument('role');
        $password = $input->getArgument('password');

        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();
        $user = $this->getUser();

        $now = new DateTime();

        $existing = $doctrine->getRepository(get_class($user))->findOneBy(array('username' => $username));

        if ($existing) {
            $output->writeln(sprintf('<error>User "%s" already exists</error>',
                $username
            ));

            return 1;
        }

        if (!in_array($locale, $userLocales)) {
            $output->writeln(sprintf(
                'Given locale "%s" is invalid, must be one of "%s"',
                $locale, implode('", "', $userLocales)
            ));

            return 1;
        }

        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);

        $em->persist($contact);
        $em->flush();

        $user->setContact($contact);
        $user->setUsername($username);
        $user->setSalt($this->generateSalt());
        $user->setPassword($this->encodePassword($user, $password, $user->getSalt()));
        $user->setLocale($locale);
        $user->setEmail($email);

        $role = $doctrine->getRepository('SuluSecurityBundle:Role')->findOneBy(array('name' => $roleName));

        if (!$role) {
            $output->writeln(sprintf('<error>Role "%s" not found. The following roles are available: "%s"</error>',
                $roleName,
                implode('", "', $this->getRoleNames())
            ));

            return 1;
        }

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale(json_encode($locales)); // set all locales
        $em->persist($userRole);

        $em->persist($user);
        $em->flush();

        $output->writeln(
            sprintf('Created user "<comment>%s</comment>" in role "<comment>%s</comment>"', $username, $roleName)
        );
    }

    /**
     * Returns a new instance of the user.
     * Can be overwritten to use a different implementation.
     *
     * @return UserInterface
     */
    protected function getUser()
    {
        return new User();
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $roleNames = $this->getRoleNames();
        $helper = $this->getHelper('question');
        $doctrine = $this->getDoctrine();
        $userLocales = $this->getContainer()->getParameter('sulu_core.locales');

        if (!$input->getArgument('username')) {
            $question = new Question('Please choose a username: ');
            $question->setValidator(
                function ($username) use ($doctrine) {
                    if (empty($username)) {
                        throw new \InvalidArgumentException('Username can not be empty');
                    }

                    $users = $doctrine->getRepository('SuluSecurityBundle:User')->findBy(
                        array('username' => $username)
                    );
                    if (count($users) > 0) {
                        throw new \InvalidArgumentException(sprintf('Username "%s" is not unique', $username));
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
                function ($firstName) use ($doctrine) {
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
                function ($lastName) use ($doctrine) {
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
                function ($email) use ($doctrine) {
                    if (empty($email)) {
                        $email = null;
                    }
                    if ($email !== null) {
                        $users = $doctrine->getRepository('SuluSecurityBundle:User')->findBy(
                            array('email' => $email)
                        );
                        if (count($users) > 0) {
                            throw new \InvalidArgumentException(sprintf('Email "%s" is not unique', $email));
                        }
                    }

                    return $email;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('email', $value);
        }

        if (!$input->getArgument('locale')) {
            $question = new ChoiceQuestion('Please choose a locale', $userLocales);
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
                function ($password) use ($doctrine) {
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
     * Generates a random salt for the password.
     *
     * @return string
     */
    private function generateSalt()
    {
        return $this->getContainer()->get('sulu_security.salt_generator')->getRandomSalt();
    }

    /**
     * Encodes the given password, for the given password, with he given salt and returns the result.
     *
     * @param $user
     * @param $password
     * @param $salt
     *
     * @return mixed
     */
    private function encodePassword($user, $password, $salt)
    {
        $encoder = $this->getContainer()->get('security.encoder_factory')->getEncoder($user);

        return $encoder->encodePassword($password, $salt);
    }

    /**
     * Return the names of all the roles.
     *
     * @return array
     *
     * @throws RuntimeException If no roles exist
     */
    private function getRoleNames()
    {
        $roleNames = $this->getDoctrine()->getRepository('SuluSecurityBundle:Role')->getRoleNames();

        if (empty($roleNames)) {
            throw new \RuntimeException(sprintf(
                'The system currently has no roles. Use the "sulu:security:role:create" command to create roles.'
            ));
        }

        return $roleNames;
    }

    /**
     * Return the doctrine service.
     *
     * @return Doctrine\Common\Persistence\ManagerRegistry
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }
}
